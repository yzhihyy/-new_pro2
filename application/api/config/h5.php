<?php

return [
    // 平台主题
    'platform_theme' => config('app.app_host') . '/h5/v3_4_0/themeDetails.html?theme_id=%d',
    // 个人主题
    'personal_theme' => config('app.app_host') . '/h5/v3_4_0/personalTheme.html?theme_id=%d',
    // 平台主题文章
    'platform_theme_article' => config('app.app_host') . '/h5/v3_4_0/articleDetails.html?theme_id=%d&article_id=%d',
    // 个人主题文章
    'personal_theme_article' => config('app.app_host') . '/h5/v3_4_0/personalArticle.html?theme_id=%d&article_id=%d',
];