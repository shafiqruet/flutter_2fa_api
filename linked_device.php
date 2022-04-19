<?php if (!defined('ABSLPATHROOT')) exit('No direct script access allowed');
require_once ABSLPATHROOT . 'models/users_linked_device.php';
require_once ABSLPATHROOT . 'models/push_notification_history.php';
require_once ABSLPATHROOT . 'library/kb_signup.php';
$usersLinkedDevice = new UsersLinkedDevice();
$pushNotificationHistory = new PushNotificationHistory();
$kbUserSignup = new KBUserSignup();

if (isset($_SESSION['login_info']['device_token']) && isset($_POST['action']) && $_POST['action'] == 'check_linked_device') {
    $uid = $_SESSION['login_info']['uid'];
    $device_token = trim($_POST['token_value']);
    $current_time = date('Y-m-d H:i:s');
    $time_remain = trim($_POST['time_remain']);
    if ($device_token == $_SESSION['login_info']['device_token']) {
        $where = [
            'uid' => $uid,
            'is_linked' => 1,
            'token' => $device_token
        ];
        $user_linked_line = $usersLinkedDevice->get($where, '*');
        if (!empty($user_linked_line)) {
            $where = [
                'uid' => $uid,
                'is_running' => 1,
                'token' => $device_token
            ];
            $mobile_device_line = $pushNotificationHistory->get($where, '*', 'ID DESC');
            if (!empty($mobile_device_line)) {
                if($time_remain == '0:00'){
                    unset($_SESSION['login_info']);
                    echo 'login';
                } else {
                    $linked_id = $mobile_device_line['id'];
                    $operation_status = $mobile_device_line['operation_status'];
                    $current_time_format = date("Y-m-d H:i:s", strtotime($current_time) - 5);
                    // date now
                    $db_time_format = date("Y-m-d H:i:s", strtotime($mobile_device_line['created']));
                    // calculate the difference
                    $difference = strtotime($current_time_format) - strtotime($db_time_format);
                    $difference_in_minutes = $difference / KBConstant::APPS_LINKED_EXPIRE_TIME;

                    if ($difference_in_minutes > 1) {
                        $kbUserSignup->processLinkedDevice($linked_id, 0);
                        echo 'login';
                    } else {
                        if ($operation_status == 1) {
                            $kbUserSignup->processLinkedDevice($linked_id, 0);
                            $_SESSION['loggedin_userid'] = $uid;
                            $_SESSION['loggedin_usertype'] = $_SESSION['login_info']['unitpackage'];
                            $_SESSION['loggedin_username'] = $_SESSION['login_info']['username'];
                            $_SESSION['login_from'] = "FB Universe";
                            unset($_SESSION['login_info']);
                            echo 'member';
                        } else if ($operation_status == 2) {
                            $kbUserSignup->processLinkedDevice($linked_id, 0);
                            unset($_SESSION['login_info']);
                            echo 'login';
                        }
                    }
                }

            } else {
                unset($_SESSION['login_info']);
                echo 'login';
            }
        } else {
            unset($_SESSION['login_info']);
            echo 'login';
        }
    } else {
        unset($_SESSION['login_info']);
        echo 'login';
    }

} else {
    unset($_SESSION['login_info']);
    echo 'login';
}


