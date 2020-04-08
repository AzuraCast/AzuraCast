<?php

use Slim\App;

return function (App $app) {

    call_user_func(include(__DIR__ . '/routes/admin.php'), $app);
    call_user_func(include(__DIR__ . '/routes/api.php'), $app);
    call_user_func(include(__DIR__ . '/routes/base.php'), $app);
    call_user_func(include(__DIR__ . '/routes/public.php'), $app);
    call_user_func(include(__DIR__ . '/routes/stations.php'), $app);

};
