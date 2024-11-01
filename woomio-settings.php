<?php
class WoomioBloggerSettingsPage
{
    //const WOOMIO_URL = 'http://test.woomio.com';
    const WOOMIO_URL = 'https://api.woomio.com';

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Woomio',
            'manage_options',
            'woomio-blogger-setting-admin',
            array( $this, 'create_admin_page' )
        );
    }


    //helper functions
    public function woomio_loginform_display()
    {
        $data = get_option("woomio_blogger_option_name");

        return ctype_digit($data["woomio_blogger_id"]) ? "display:none;":"";
    }

    public function woomio_blogger_post_form_display()
    {
        $data = get_option("woomio_blogger_option_name");
        return ctype_digit($data["woomio_blogger_id"]) ? "":"display:none;";
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'woomio_blogger_option_name' );
?>

<div class="wrap">
    <h2>Woomio Settings</h2>
    <div id="loginForm" style=<?php echo $this->woomio_loginform_display(); ?> >
        <h4>Please Login to Woomio with your Facebook account</h4>
        <input type="button" onclick="connect();" class="button button-primary" value="Connect to Woomio" />
        <hr/>
        <h4>You could also choose to login to Woomio with your email</h4>
        <input type="email" placeholder="Email" id="emailtxt">
        <br/>
        <input type="password" placeholder="Password" id="passwordtxt">
        <br/>
        <input type="button" onclick="login();" class="button button-primary" value="Connect with Email">
        <hr/>
        <div id="status" style="color:red;font-size:20px;margin-top:10px;"></div>
    </div>

    <form method="post" action="options.php" id="woomio_blogger_post_form" style=<?php echo $this->woomio_blogger_post_form_display(); ?> >
      <div>
        You are logged in to Woomio.
      </div>
<?php
        // This prints out all hidden setting fields
        settings_fields( 'woomio_blogger_option_group' );
        do_settings_sections( 'woomio-blogger-setting-admin' );
        //submit_button();
?>
    </form>
</div>

<script type="text/javascript">
var $ = jQuery; //WordPress Admin already has jQuery included in no conflict mode
var domain = "<?php echo self::WOOMIO_URL; ?>";
var blogDomain = "<?php echo $_SERVER['SERVER_NAME']; ?>";
function renderStatus(statusText) {
    $("#status").html(statusText);
}

function connect() {
    // Get code
    $.ajax({
        url: domain + "/api/OAuth/Code",
        async: false
    }).done(function (data) {
        authenticate(data);
    });
}

function authenticate(code) {
    var win = window.open(domain + "/api/RemoteFbAuth/Connect?wcode=" + code, "", "width=1000，height=560");
    var winInterval = setInterval(function () {
        if (win.closed) {
            clearInterval(winInterval);
            getToken(code);
        }
    }, 1000);
}

function getToken(code) {
    // Get code
    //console.log("Get code :"+code);
    $.ajax({
        url: domain + "/api/OAuth/Token?code=" + code,
        async: false
    }).done(function (data) {
        //console.log("Step to getToken!!! The token is:"+ data);
        getBlogger(data, function (bloggerId) {
            if (data == null) {
                $("#status").html("Fail to Login.");
            }
            else {
                $("#woomio_blogger_id").val(bloggerId.replace(/"/g, ""));
                $("#woomio_convertlink_checkbox").prop("checked", true);
                //We save the data immediately so that the user does not have to connect to Woomio next time, if they forget to hit save
                $.ajax({
                    //url: window.location.href,
                    url: 'options.php',
                    data: $("#woomio_blogger_post_form").serialize(),
                    type: "POST",
                    success : function() {
                        console.log("post success!");
                        $("#loginForm").hide();
                        $("#woomio_blogger_post_form").show();
                    }
                });
            }
        }, function (errorMessage) {
            renderStatus("Cannot get woomio blogger right now!" + errorMessage);
        });
    });
}

function getBlogger(token, callback, errorCallback) {
    var wooUrl = domain + "/api/endpoints/GetBlogger?token=" + token + "&site=" + blogDomain;
    var x = new XMLHttpRequest();
    x.open('GET', wooUrl);
    x.responseType = '';
    x.onload = function () {
        var bloggerId = x.responseText;
        if (!bloggerId || bloggerId.length === 0) {
            errorCallback('No response from Woomio Server!');
            return;
        }
        callback(bloggerId);
    };
    x.onerror = function () {
        errorCallback('Network error.');
    };
    x.send();
}

function login(){
    var email = $("#emailtxt").val();
    var password = $("#passwordtxt").val();
  data ={ email:email, password:password, site:blogDomain};
    $.ajax(domain + '/api/cauth/wpclientlogin', {
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function (data) {
                if (data != "error" && data != null ) {
                    $("#woomio_blogger_id").val(data.replace(/"/g, ""));
                    $("#woomio_convertlink_checkbox").prop("checked", true);
                    $.ajax({
                        url: 'options.php',
                        data: $("#woomio_blogger_post_form").serialize(),
                        type: "POST",
                        success : function() {
                            console.log("post success!");
                            $("#loginForm").hide();
                            $("#woomio_blogger_post_form").show();
                        }
                    });
                }
                else {
                    renderStatus("Fail to Login.");
                }
            },
            error: function (errorMessage) {
                 renderStatus("Cannot get woomio blogger right now! </br>" + errorMessage.responseText);
            }
        });
}
</script>

<?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'woomio_blogger_option_group', // Option group
            'woomio_blogger_option_name' // Option name
             //array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            '', // Title
            array( $this, 'print_section_info' ), // Callback
            'woomio-blogger-setting-admin' // Page
        );

        /*add_settings_field(
            'woomio_convertlink_checkbox',
            'Enable auto affiliate link conversion',
            array( $this, 'woomio_convertlink_checkbox_callback' ),
            'woomio-blogger-setting-admin',
            'setting_section_id'
        );*/

        //Set convertlink_checkbox to checked as default
        //update_option('woomio_convertlink_checkbox', 'on');

        add_settings_field(
            'woomio_blogger_id', // ID
            '', // Title
            array( $this, 'woomio_blogger_id_callback' ), // Callback
            'woomio-blogger-setting-admin', // Page
            'setting_section_id' // Section
        );
    }


    /**
     * Print the Section text
     */
    public function print_section_info()
    {
       // print 'Please Login to woomio with your facebook account';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function woomio_blogger_id_callback()
    {
        printf(
            '<input type="hidden" id="woomio_blogger_id" name="woomio_blogger_option_name[woomio_blogger_id]" value=%s />',
            isset( $this->options['woomio_blogger_id'] ) ? $this->options['woomio_blogger_id'] : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    /* public function woomio_convertlink_checkbox_callback()
    {
        printf(
            '<input type="checkbox" id="woomio_convertlink_checkbox" name="woomio_blogger_option_name[woomio_convertlink_checkbox]" %s />',
            isset( $this->options['woomio_convertlink_checkbox'] ) ? (esc_attr( $this->options['woomio_convertlink_checkbox'])=='on' ? 'checked' : '' ) : ''
        );
    } */

}
