<?php

$whatsappServiceURL = env('WHATSAPP_SERVICE_V2_URL');

return [

    'WHATSAPP_SERVICE_V2_URL' => $whatsappServiceURL,

    'WHATSAPP_SERVICE_V2_URL_API_KEY' => env('WHATSAPP_SERVICE_V2_URL_API_KEY'),
    'WHATSAPP_SERVICE_V2_URL_CREATE_INSTANCE' => $whatsappServiceURL . '/instance/create',
    'WHATSAPP_SERVICE_V2_URL_FETCH_INSTANCE' => $whatsappServiceURL . '/instance/fetchInstances',
    'WHATSAPP_SERVICE_V2_URL_RECONNECT_INSTANCE' => $whatsappServiceURL . '/instance/connect',
    'WHATSAPP_SERVICE_V2_URL_FETCH_PROFILE' => $whatsappServiceURL . '/chat/fetchProfile',
    'WHATSAPP_SERVICE_V2_URL_LOGOUT_INSTANCE' => $whatsappServiceURL . '/instance/logout',
    'WHATSAPP_SERVICE_V2_URL_DELETE_INSTANCE' => $whatsappServiceURL . '/instance/delete',
    'WHATSAPP_SERVICE_V2_URL_GET_PHOTO_PROFILE' => $whatsappServiceURL . '/chat/fetchProfilePictureUrl', // + instanceId
    'WHATSAPP_SERVICE_V2_URL_DOWNLOAD_MEDIA_MESSAGE' => $whatsappServiceURL . '/chat/getBase64FromMediaMessage', // + instanceId
    'WHATSAPP_SERVICE_V2_URL_SEND_TEXT_MESSAGE' => $whatsappServiceURL . '/message/sendText', // + instanceId
];
