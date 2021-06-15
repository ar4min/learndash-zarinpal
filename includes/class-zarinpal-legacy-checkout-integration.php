<?php
/**
* Zarinpal legacy checkout integration class
*/

class LearnDash_Zarinpal_Legacy_Checkout_Integration {
	
	/**
	 * Plugin options
	 * @var array
	 */
	private $options;

    private $MerchantID;

    private $zaringit = false;
    
    private $return_url;
    
    private $test_mode = false;
    
	private $default_button;

	/**
	 * Variable to hold the Zarinpal Button HTML. This variable can be checked from other methods.
	 */
	private $zarinpal_button;

    private $dropdown_button;
	/**
	 * Variable to hold the Course object we are working with.
	 */
	private $course;

	
	private $zarinpal_script_loaded_once = false;


	/**
	 * Class construction function
	 */
	public function __construct() {
		$this->options         			= 	get_option( 'learndash_zarinpal_settings', array() );
	
	    $this->test_mode    =   @$this->options['test_mode'];
        $this->MerchantID   =   @$this->options['MerchantID'];
        $this->zaringit   =   @$this->options['zaringit'];
        $this->return_url   =   @$this->options['return_url'];
        
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'learndash_payment_button', array( $this, 'payment_button' ), 10, 2 );
		//add_filter( 'learndash_dropdown_payment_button', array( $this, 'payment_button' ), 10, 2 );

		add_action( 'init', array( $this, 'process_checkout' ) );
		
		add_action( 'get_footer', array( $this, 'get_footer' ) );
	}

	/**
	 * Load necessary scripts and stylesheets
	 */
	public function enqueue_scripts() {
       wp_enqueue_style( 'ld-zarinpal-style', LEARNDASH_ZARINPAL_PLUGIN_URL . 'assets/css/learndash-zarinpal-style.css', array(), LEARNDASH_ZARINPAL_VERSION );
	}


	function get_footer() {
		if ( is_admin() ) return;
		
		if ( empty( $this->zarinpal_button ) ) {
			wp_dequeue_script('learndash_zarinpal_checkout_handler');
		}
	}
	
	public function payment_button( $default_button, $params = null ) {
	
		// Also ensure the price it not zero
		if ( ( !isset( $params['price'] ) ) || ( empty( $params['price'] ) ) ) {
			return $default_button;
		}
		
		$this->default_button = $default_button;

		if (isset($params['post'])) {
			$this->course = $params['post'];
		}

		$this->zarinpal_button = $this->zarinpal_button();
		
		if (!empty($this->zarinpal_button))
			return $default_button . $this->zarinpal_button();
		else 
			return $default_button;
	}

	/**
	 * Process zarinpal checkout
	 */
	public function process_checkout() {
	    
	    if (!empty($_REQUEST['Status']) && $_REQUEST['Status'] == 'OK') {
		  if (isset($_REQUEST['c'])){
		    $meta                = get_post_meta( $_REQUEST['c'], '_sfwd-courses', true );
	        $course_price        = @$meta['sfwd-courses_course_price'];
	        $course_price_copy        = @$meta['sfwd-courses_course_price'];
	        
            $course_price = str_replace("تومان","",$course_price);
            $course_price = str_replace("ریال","",$course_price);
            $course_price = str_replace("$","",$course_price);
            $course_price = str_replace("هزار","",$course_price);
            
            if(is_numeric(strpos($course_price_copy,'ریال'))){
	          $course_price = $course_price / 10;
            }else if(is_numeric(strpos($course_price_copy,'تومان'))){
	          $course_price = $course_price ;
            }else if(is_numeric(strpos($course_price_copy,'هزار تومان'))){
	          $course_price = $course_price * 1000 ;
            }else{
	          $course_price = $course_price/10 ;
            }
            
            if(is_numeric(strpos($course_price_copy,'هزار تومان'))){
	          $course_price = $course_price * 1000 ;
            }
          
            $course_price = trim($course_price);
	   	    
	   	    if (isset($_REQUEST['Status'],$_REQUEST['Authority'])){
			  if (strtoupper($_REQUEST['Status']) == 'OK'){
			    $data = array('MerchantID' => $this->MerchantID, 'Authority' => $_REQUEST['Authority'], 'Amount' => intval($course_price));
                $result = $this->SendRequest_ToZarinPal('PaymentVerification', json_encode($data));
    	        if ($result === false) {
    	            $_SESSION['sfwd-lms-tx'] = 'پرداخت لغو شد';
    	            $sfwd_lms_tx = 'پرداخت لغو شد';
    	        }else {
                  if ($result["Status"] == 100) {
                    $this->zarinpal_learndash_payment_complete('ZarinPal',ltrim($_REQUEST['Authority'], 0),$result["RefID"]);
					$_SESSION['sfwd-lms-tx'] = 'پرداخت با موفقیت تکمیل شد . شماره پیگیری  : '.$result["RefID"];
					$sfwd_lms_tx = 'پرداخت با موفقیت تکمیل شد . شماره پیگیری  : '.$result["RefID"];
                  }else{
					$_SESSION['sfwd-lms-tx'] = 'خطا در تکمیل پرداخت  : '.$this->getZarinPalResponseStatus($result["Status"]);
					$sfwd_lms_tx = 'خطا در تکمیل پرداخت  : '.$this->getZarinPalResponseStatus($result["Status"]);
				  }
    	        }
			  }
		    }
		  }
		  ?>
	      <script>
          alert("<?php echo $sfwd_lms_tx; ?>");  
          </script>
	    <?php
	    }else if (!empty($_REQUEST['Status']) && $_REQUEST['Status'] == 'NOK') {
			$_SESSION['sfwd-lms-tx'] = 'پرداخت لغو شد';
		    $sfwd_lms_tx = 'پرداخت لغو شد';
		    ?>
	        <script>
            alert("<?php echo $sfwd_lms_tx; ?>");  
            </script>
	        <?php
		}
	}

    public function getZarinPalResponseStatus($code)
	{
		switch($code)
		{
			case -1:  return 'اطلاعات ارسالی ناقص می باشد';
			case -2:  return 'مرچنت معتبر نیست';
			case -3:  return 'رقم پرداختی کمتر از حداقل قابل قبول می باشد';
			case -4:  return 'مرچنت نامعتبر';
			case -11: return 'پرداخت مورد نظر یافت نشد';
			case -21: return 'عملیات مالی برای تراکنش مورد نظر یافت نشد';
			case -22: return 'تراکنش ناموفق می باشد';
			case -33: return 'رقم تراکنش با رقم پرداختی تطابق ندارد';
			case -54: return 'درخواست مورد نظر ارشیو شده است';
			case 100: return 'تراکنش با موفقیت انجام شد';
			case 101: return 'تراکنش قبلا با موفقیت انجام و تعیین وضعیت شده';
			case 'NOK': return 'پرداخت از سوی کاربر لغو شد';
		}
		return 'کد خطا '.$code;
	}
    public function zarinpal_learndash_payment_complete($gateway,$resnum,$refnum)
    {
    
      $meta                = get_post_meta( $_REQUEST['c'], '_sfwd-courses', true );
	  $course_price        = @$meta['sfwd-courses_course_price'];
	
	  $course_price = preg_replace( '/.*?(\d+(?:\.?\d+))/', '$1', $course_price );

	  if ( ! $this->is_zero_decimal_currency( $this->options['currency'] ) ) {
		$course_price = $course_price * 10;
	  }
		
	  $user_id = get_current_user_id();
	  $course_id = $_REQUEST['c'];
	  $course = get_post($course_id);
	  $user = get_userdata($user_id);
	  $course_title = $course->post_title;
	  $user_email = $user->user_email;
	  ld_update_course_access($user_id, $course_id);
	  $usermeta = get_user_meta($user_id, '_sfwd-courses', true);
	  if (empty($usermeta)) $usermeta = $course_id; else $usermeta .= ",$course_id";
	  update_user_meta($user_id, '_sfwd-courses', $usermeta);
	  $post_id = wp_insert_post(array('post_title' => "درس {$course_title} توسط کاربر {$user_email} خریداری شد", 'post_type' => 'sfwd-transactions', 'post_status' => 'publish', 'post_author' => $user_id));
	  update_post_meta($post_id, 'user_id', $user_id);
	  update_post_meta($post_id, 'user_name', $user->user_login);
 	  update_post_meta($post_id, 'user_email', $user_email);
	  update_post_meta($post_id, 'course_id', $course_id);
	  update_post_meta($post_id, 'course_title', $course_title);
	  update_post_meta($post_id, 'res_num', $resnum);
	  update_post_meta($post_id, 'ref_num', $refnum);
	  update_post_meta($post_id, 'paid_price', $course_price);
	  update_post_meta($post_id, 'gateway', $gateway);
	  update_post_meta($post_id, 'time', time());
	  update_post_meta($post_id, 'date', date('Y/m/d H:i:s'));
    }

	public function create_user( $email, $password, $username = '' ) {
		if ( empty( $username ) ) {
			$username = preg_replace( '/(.*)\@(.*)/', '$1', $email );
		}
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			if ( $user_id->get_error_code() == 'existing_user_login' ) {
				$random_chars = str_shuffle( substr( md5( time() ), 0, 5 ) );
				$username = $username . '-' . $random_chars;
				$user_id = $this->create_user( $email, $password, $username );
			}
		}

		do_action( 'learndash_zarinpal_after_create_user', $user_id );

		return $user_id;
	}

	public function is_paypal_active() {
		if ( version_compare( LEARNDASH_VERSION, '2.4.0', '<' ) ) {
			$ld_options   = learndash_get_option( 'sfwd-courses' );
			$paypal_email = isset( $ld_options['paypal_email'] ) ? $ld_options['paypal_email'] : '';
		} else {
			$paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
		}

		if ( ! empty( $paypal_email ) ) {
			return true;
		} else {
			return false;
		}
	}

    function SendRequest_ToZarinPal($action, $params){
       try {
          if($this->test_mode == '1'){
             $ch = curl_init('https://sandbox.zarinpal.com/pg/rest/WebGate/' . $action . '.json');
          }else{
             $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/' . $action . '.json');
          }
          curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params)
          ));
          $result = curl_exec($ch);
          return json_decode($result, true);
       } catch (Exception $ex) {
          return false;
       }        
    }

	/**
	 * zarinpal payment button
	 * @return string Payment button
	 */
	public function zarinpal_button() {

		if (empty($this->course)) return;
	
		$user_id = get_current_user_id();
		$user_email = '';

		$meta                = get_post_meta( $this->course->ID, '_sfwd-courses', true );
		$course_price        = @$meta['sfwd-courses_course_price'];
		
		$course_price_copy        = @$meta['sfwd-courses_course_price'];
		$course_price_type   = @$meta['sfwd-courses_course_price_type'];
		//$course_image        = get_the_post_thumbnail_url( $this->course->ID, 'medium' );
		$custom_button_url   = @$meta['sfwd-courses_custom_button_url'];
		//$currency            = strtolower( $this->options['currency'] );

		$course_interval_count = get_post_meta( $this->course->ID, 'course_price_billing_p3', true );
		$course_interval       = get_post_meta( $this->course->ID, 'course_price_billing_t3', true );

		$course_name      = $this->course->post_title;
		$course_id        = $this->course->ID;
		$course_plan_id   = 'learndash-course-' . $this->course->ID;

		$course_price = preg_replace( '/.*?(\d+(?:\.?\d+))/', '$1', $course_price );
       	?>
		<style>
          .learndash_paypal_button{
            display: none; 
          }
          .ld-course-status-content .ld-course-status-price .ld-currency{
             display: none;
          }
          .jq-dropdown-menu{
              margin-left: -26px !important;
          }
          .ld-course-status-content .ld-course-status-action span.ld-text:nth-child(4) {
            display: none !important;
          }
        </style>
        <?php
		$course_price = str_replace("تومان","",$course_price);
        $course_price = str_replace("ریال","",$course_price);
        $course_price = str_replace("$","",$course_price);
        $course_price = str_replace("هزار","",$course_price);
        //var_dump($course_price);
		if ( $this->is_paypal_active() ) {
			$zarinpal_button_text  = apply_filters( 'learndash_zarinpal_purchase_button_text', __( 'Use a Credit Card', 'learndash-zarinpal' ) );		
		} else {
			if (class_exists('LearnDash_Custom_Label')) {
				$zarinpal_button_text  = apply_filters( 'learndash_zarinpal_purchase_button_text', LearnDash_Custom_Label::get_label( 'button_take_this_course' ) );		
			} else {
				$zarinpal_button_text  = apply_filters( 'learndash_zarinpal_purchase_button_text', __( 'Take This Course', 'learndash-zarinpal' ) );
			}
		}
        
        if(is_user_logged_in()){
          //$this->return_url
          $ZPLUrl = add_query_arg(array('c'=>$course_id),$this->return_url);
          //$ZPLUrl = add_query_arg(array('c'=>$course_id),get_home_url());
          
          if(is_numeric(strpos($course_price_copy,'ریال'))){
	        $course_price = $course_price / 10;
          }else if(is_numeric(strpos($course_price_copy,'تومان'))){
	        $course_price = $course_price ;
          }else{
	        $course_price = $course_price/10 ;
          }
          
          if(is_numeric(strpos($course_price_copy,'هزار تومان'))){
	        $course_price = $course_price * 1000 ;
          }
          //$course_price = str_replace("هزار","",$course_price);
          
          $course_price = trim($course_price);
          //var_dump($course_price_copy);
          $data = array('MerchantID' => $this->MerchantID, 'Amount' => intval($course_price), 'CallbackURL' => $ZPLUrl, 'Description' => 'test' , 'Email' =>'test@gmail.com');
        
          $result = $this->SendRequest_ToZarinPal('PaymentRequest', json_encode($data));
          //var_dump($result);
    	  if ($result === false) {
            $zarinpal_button .= '<div class="learndash_checkout_button learndash_stripe_button">
	          <button class="learndash-stripe-checkout-button btn-join button"><i class="vc_btn3-icon fa fa-check-square-o"></i> خطا ('.getZarinPalResponseStatus($r->Status).') در پرداخت زرین پال</button>
            </div>';
          } else {
            if ($result["Status"] == 100) {
              if($this->test_mode == true){
                $zarinpal_button .= '<div class="learndash_checkout_button learndash_stripe_button">
                <form action="https://sandbox.zarinpal.com/pg/StartPay/'.$result["Authority"].'" method="get" class="learndash-stripe-checkout">
                   <button type="submit" class="learndash-stripe-checkout-button btn-join button">پرداخت با زرین پال</button>
                </form></div>';
              }else{
                if($this->zaringit == true){
                  $zarinpal_button .= '<div class="learndash_checkout_button learndash_stripe_button">
                  <form action="https://www.zarinpal.com/pg/StartPay/'.$result["Authority"].'./ZarinGate" method="get" class="learndash-stripe-checkout">
                    <button type="submit" class="learndash-stripe-checkout-button btn-join button">پرداخت با زرین پال</button>
                  </form></div>';
                }else if($this->zaringit == false){
                  $zarinpal_button .= '<div class="learndash_checkout_button learndash_stripe_button">
                  <form action="https://www.zarinpal.com/pg/StartPay/'.$result["Authority"].'" method="get" class="learndash-stripe-checkout">
                    <button type="submit" class="learndash-stripe-checkout-button btn-join button">پرداخت با زرین پال</button>
                  </form></div>';  
                }
              }
            }else{
              $zarinpal_button .= '<div class="learndash_checkout_button learndash_stripe_button">
              <div class="learndash-stripe-checkout">
	            <button class="learndash-stripe-checkout-button btn-join button"><i class="vc_btn3-icon fa fa-check-square-o"></i> خطا ('.getZarinPalResponseStatus($r->Status).') در پرداخت زرین پال</button>
              </div></div>';
            }
          }
        }else{
           $login_model = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
  		   /** This filter is documented in themes/ld30/includes/shortcode.php */
		   $login_url = apply_filters( 'learndash_login_url', ( 'yes' === $login_model ? '#login' : wp_login_url( get_permalink() ) ) );
           $zarinpal_button = '<a class="ld-button" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login to Enroll', 'learndash' ) . '</a></span>';
           
        }
		return $zarinpal_button;
	}

	public function is_zero_decimal_currency( $currency = '' ) {
		$currency = strtoupper( $currency );

		$zero_decimal_currencies = array(
			'IRT',
		    'IRR'
		);

		if ( in_array( $currency, $zero_decimal_currencies ) ) {
			return true;
		} else {
			return false;
		}
	}
}

new LearnDash_Zarinpal_Legacy_Checkout_Integration();