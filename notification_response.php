<?php
session_start();
require_once '../config/custom.php';
require_once ABSLPATHROOT . 'models/users_linked_device.php';
require_once ABSLPATHROOT . 'models/push_notification_history.php';
require_once ABSLPATHROOT . 'library/kb_signup.php';
$usersLinkedDevice = new UsersLinkedDevice();
$pushNotificationHistory = new PushNotificationHistory();
$kbUserSignup = new KBUserSignup();

$post_params = [
    'resultCode' => 401,
    'resultText' => 'Invalid Api Request'
];

if (!empty($_POST['token_value'])) {
    $action_type = trim($_POST['action_type']);
    $device_token = trim($_POST['token_value']);
    $uid = trim($_POST['user_id']);

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
            'token' => $device_token,
            'operation_status' => 0
        ];
        $mobile_device_line = $pushNotificationHistory->get($where, '*', 'ID DESC');
        if (!empty($mobile_device_line)) {
            $current_time = date('Y-m-d H:i:s');
            $current_time_format = date("Y-m-d H:i:s", strtotime($current_time));
            $db_time_format = date("Y-m-d H:i:s", strtotime($mobile_device_line['created']));
            $difference = strtotime($current_time_format) - strtotime($db_time_format);
            $difference_in_minutes = $difference / KBConstant::APPS_LINKED_EXPIRE_TIME;
            $session_data = json_decode($mobile_device_line['session_data'], true);
             $id = $mobile_device_line['id'];
            if ($difference_in_minutes <= 1) {
                $res = $kbUserSignup->verifyLinkedDevice($uid, $device_token, $session_data);
                if($res == KBUserSignup::LOGIN_RESULT_OK){
                    $data = [
                        'operation_status' => $action_type,
                        'called_time' => $current_time
                    ];

                    $where = [
                        'uid' => $uid,
                        'token' => $device_token,
                        'is_running' => 1,
                        'id' => $id
                    ];
                    $pushNotificationHistory->save($data, $where);
                    $post_params = [
                        'resultCode' => 0,
                        'resultText' => 'Api Request Successful'
                    ];

                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($post_params);
}

