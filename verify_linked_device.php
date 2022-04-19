<?php if (!defined('ABSLPATHROOT')) exit('No direct script access allowed');
if (isset($_SESSION['login_info']['device_token'])) {
    require_once ABSLPATHROOT . 'models/users_linked_device.php';
    require_once ABSLPATHROOT . 'models/push_notification_history.php';
    $usersLinkedDevice = new UsersLinkedDevice();
    $pushNotificationHistory = new PushNotificationHistory();
    $uid = $_SESSION['login_info']['uid'];
    $token_value = $_SESSION['login_info']['device_token'];
    $where = ['uid' => $uid, 'token' => $token_value];
    $user_linked_line = $usersLinkedDevice->get($where, '*');
    $allow_api_call = 0;

    if ($user_linked_line['is_linked'] == 1 && $user_linked_line['token'] != '') {
        $current_time = date('Y-m-d H:i:s');
        $where = [
            'uid' => $uid,
            'is_running' => 1,
            'token' => $token_value
        ];
        $mobile_device_line = $pushNotificationHistory->get($where, '*', 'ID DESC');
        if (!empty($mobile_device_line)) {
            $linked_id = $mobile_device_line['id'];
            $find_access = $mobile_device_line['operation_status'];
            $current_time_format = date("Y-m-d H:i:s", strtotime($current_time));
            // date now
            $db_time_format = date("Y-m-d H:i:s", strtotime($mobile_device_line['created']));
            // calculate the difference
            $difference = strtotime($current_time_format) - strtotime($db_time_format);
            $difference_in_minutes = $difference / KBConstant::APPS_LINKED_EXPIRE_TIME;

            if ($difference_in_minutes > 1) {
                $allow_api_call = 1;
            }
        } else {
            $allow_api_call = 1;
        }

        if ($allow_api_call == 1) {
            $data = [
                'uid' => $uid,
                'token' => $token_value,
                'is_running' => 1,
                'operation_status' => 0,
                'session_data' => json_encode($_SESSION['login_info']),
                'created' => $current_time
            ];
            $isSaved = $pushNotificationHistory->save($data);
            if ($isSaved) {
                $request_data = [
                    "to" => $token_value,
                    "notification" => array("title" => "Please click notification", "body" => "Please click Yes button if you want to login FB Universe site or click No if you do not want to login FB Universe", "icon" => $HOMEPAGE_ROOT . "/assets/images/index/dream/logo.svg", "content_available" => "true", "click_action" => "FLUTTER_NOTIFICATION_CLICK", "priority" => "high", "to" => "MYTOPIC", "channelId" => "high_importance_channel"),
                    "data" => array(
                        "user_id" => $uid,
                        "clickAction" => "FLUTTER_NOTIFICATION_CLICK",
                        "priority" => "high",
                        "token_value" => $token_value
                    )
                ];
                $data_string = json_encode($request_data);

                $headers = array('Authorization: key=AAAAVEleqR0:APA91bGeWgN-8OtMr3Us4ntIl1PH6waFdkl78KXnMGYwhYuzgw7QRa7dqUbmdYMOPZ614yFiEVYDVBt6wfla1aufMa9QjGclniQJsU1jBTt4XlBvjRAWFPTw6z6NRdKl_R8lNxLeQ_qV', 'Content-Type: application/json');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $result = curl_exec($ch);
                curl_close($ch);
            }

            ?>
            <div class="verify-2fa custom_form_content">
                <div class="container">
                    <div class="verify-2fa-form-wrapper">
                        <div class="verify-2fa-form-title"><?= LABEL_LOGIN_HEADER_2FA_CODE; ?></div>
                        <div class="verify-2fa-form-disclaimer-wrapper">
                            <img src="<?= $HOMEPAGE_ROOT; ?>/assets/images/sign-up/Info.svg" alt=""
                                 class="step-2-form-disclaimer-info">
                            <div class="verify-2fa-form-disclaimer"><?= LANG_INFO_TEXT_2FA_APPS; ?></div>
                        </div>
                        <div class="notification-content-area">
                            <div class="custom_form_content-title">
                                <div class="custom_form_content-end-time"> <?= LANG_INFO_TEXT_2FA_APPS_END_TIME; ?></div>
                                <div id="counter"
                                     class="custom_form_content-wrapper"><?= KBConstant::APPS_LINKED_EXPIRE_TIME ?></div>
                            </div>
                            <div class="custom_form_content-wait"><?= LANG_INFO_TEXT_2FA_APPS_WAIT ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            unset($_SESSION['login_info']);
            header('Location: index.php?page=login');
        }
    } else {
        unset($_SESSION['login_info']);
        if ((strpos($_SERVER['HTTP_REFERER'], "/shop") !== false)) {
            header('Location: index.php?page=shoplogin');
        } else {
            header('Location: index.php?page=login');
        }
    }
} else {
    unset($_SESSION['login_info']);
    header('Location: index.php');
}
?>

<?php
if ($allow_api_call == 1) { ?>
    <style>
        .notification-content-area {
            max-width: 730px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            border: 1px solid rgba(165, 110, 87, 0.5);
            padding: 10px;
        }

        .custom_form_content {
            min-height: 90vh;
        }

        .custom_form_content-end-time {
            float: left;
            margin-right: 10px;
        }

        .custom_form_content-title {
            margin-bottom: 10px;
        }
    </style>
    <script>
        function countdown() {
            let seconds = <?=KBConstant::APPS_LINKED_EXPIRE_TIME?>;

            function showCountDownTimer() {
                let counter = $("counter");
                seconds--;
                $('#counter').html("0:" + (seconds < 10 ? "0" : "") + String(seconds));
                if (seconds > 0) {
                    setTimeout(showCountDownTimer, 1000);
                }
            }

            showCountDownTimer();
        }

        countdown();

        function checkSecond(sec) {
            if (sec < 10 && sec >= 0) {
                sec = "0" + sec
            }
            if (sec < 0) {
                sec = seconds - 1;
            }
            return sec;
        }

        setInterval(function () {
            checkLogin()
        }, 3000);

        function checkLogin() {
            let time_remain = $('#counter').html();
             $.ajax({
                type: "POST",
                data: "action=check_linked_device&token_value=<?=$token_value?>&time_remain="+time_remain,
                url: '<?= $HOMEPAGE_ROOT; ?>/route.php?ajax_page=linked_device',
                dataType: 'text',
                success: function (text) {
                    if (text == 'member') {
                        document.location.href = "members.php";
                    } else if (text == 'login') {
                        document.location.href = "index.php?page=login";
                    }
                }
            });
        }

    </script>
<?php }
?>

