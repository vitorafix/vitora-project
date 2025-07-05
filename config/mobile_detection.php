<?php

return [
    'user_agent_regex' => [
        // الگوی کامل User-Agent برای تشخیص دستگاه‌های موبایل
        'mobile' => '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|rim)|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',

        // الگوی User-Agent برای تشخیص دستگاه‌های موبایل (4 کاراکتر اول)
        'mobile_substr' => '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|er)|ai(ko|gu)|al(av|ca|co)|amoi|an(d|on)|aq(io|or)|au(t|v) |go.w|digi|haie|hi(tn|o)|hp( i|ip)|hs(c|t)|ht(c|t)|in(di|nx)|ipaq|iq(at|ct)|jw(b|nd|rd)|kp(ar|us)|ky(e|l)|lo(li|on)|mobi|mz(do|ro)|n(ev|ew)|owg1|p(ap|si)|sm(al|ri|te)|sz(mo|t)|up\.(|api|bim|cdm|cp(ar|im|sm)|wm|yr)|zte/i',
    ],
    // می‌توانید در آینده تنظیمات دیگری مربوط به تشخیص موبایل را اینجا اضافه کنید
];

