<?php
require_once '../config/custom.php';
require_once ABSLPATHROOT . 'models/users_linked_device.php';

$usersLinkedDevice = new UsersLinkedDevice();

$post_params = [
    'resultCode' => 401,
    'resultText' => 'Invalid Api Request'
];

$security = trim($_POST['security']);
if ($security == KBConstant::APPS_LINKED_SECURITY_CODE) {
    $token = trim($_POST['token_value']);
    $uid = trim($_POST['userid']);
    $security_code = trim($_POST['security_code']);

    $where = [
        'uid' => $uid,
        'security_code' => $security_code
    ];
    $linkDeviceLine = $usersLinkedDevice->get($where);
    if (!empty($linkDeviceLine) && $linkDeviceLine['is_linked'] == 0) {
        $data = [
            'is_linked' => 1,
            'token' => $token,
            'updated' => date("Y-m-d H:i:s")
        ];
        $where = [
            'uid' => $uid,
            'security_code' => $security_code
        ];
        $linkDeviceLine = $usersLinkedDevice->save($data, $where);
        if ($linkDeviceLine) {
            $post_params = [
                'resultCode' => 0,
                'resultText' => 'Mobile Apps Linked successfully'
            ];
        }
    } else if (!empty($linkDeviceLine) && $linkDeviceLine['is_linked'] == 1) {
        $post_params = [
            'resultCode' => 402,
            'resultText' => 'Already linked with 2fa'
        ];
    } elseif (empty($linkDeviceLine)) {
        $post_params = [
            'resultCode' => 403,
            'resultText' => 'Please enable 2fa then try again'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($post_params);

