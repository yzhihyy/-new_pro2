<?php

return [
    // Alipay APP支付宝配置信息
    'alipay_config' => [
        // 应用ID
        'app_id' => '2018082061049969',
        // 接口标题
        'title' => config('app.app_name'),
        // 日志路径
        'log_path' => '/alipay/app',
        // 开发者私钥
        'rsa_private_key' => 'MIIEpQIBAAKCAQEA1BRj4FDTKovaOq/YtVC7ThxjGBo2YzVhYl+C299EMTr3kiCU7jgsFsH8KItTtS0S/2IVZuFNKc9btt3K+3qfPjBL1ttFfdu3w1BMuxuFyOAoNjGQrCRArI0VXP7K+34mVn1fq18GITH42+Bnb/SiZ6/vCTx/Kn5BHB67zj51TcasO+NPYXIJADr42vz8h5Ct9U5dlzyi9RrJtpzO87Dv/5cw/nAr+THjB4bMy6Yc/3Loemn1QmDmxUMObuLsPAnQ/2i8Goy3wnJYzd8nQgmYwm3g3ipMl1llxlX/T9nfsOYCDrn2Xsz/vD3Cj6v5SDB2yAg2wqKVLe5SPl3FRvGZDwIDAQABAoIBAQCmEDVIr2E6XnoKHCmPiGCyQC4j8FqIAoN32RwJeODXv7mdlZ+ojRmQ1GLTiI2KP3oxuSbTATY/t9uz7CYGFrVcp8qqudXHQGW7LUR3+oweh89U6CjFcjmmI28H+4cLuHLipJdmBCkzkwKvgR7dnmwQzsVYsSNOLcBj+XjLfUKzG6F3l3tCKnDWlhynGfL32uAIieSI8F6d/s2ct+ldCcUDlR12gf1vTQ3uo7IoprcsvTmSU7VD19heJxDAKL1MCz/L6eU+avQDiTl8WgseOGk7/hlQaI0xBJxmHkPgydcxLNateDlLFPlkzxnFyNmP7zUgKUS1fcqoPGH2Z7Kpm0KhAoGBAPm6CiQhWxaGvmXuVBPEUOD2YUvrtv3FmsDo7In74BifvTjwQmWgJqHUKTHjMWvX+kU4RRbpMJrtL1jJQgBFtYWwwSwYsLjVt7zZ8n+dGrUhDPn4UufCSIbcEF7Oe5mYuoo5qpl0TROnZoO1LbJk62FaYl/TMsZRf25/PH+r4BXxAoGBANloP0BdJlT9Qr/3KGXF45O6xvNlIN4mB5i41B8/3Y/dtpMaW9RwbZa8mVHVUfecZF4UJdGD1KZOrcXiPh2ABuHB894MH/pxSOs72PFLbhda7ECBaVt5z3fXjnISOUDlbjs0SvpYQKeGLy/Ik3tRrHjehd0B87IoEfN2NVNjQZ7/AoGAWiy8lrlYu/Sh8gqyX4TnM6SkE5clN9PQAtLY/yZtkFa2LEZNo4bGDG+hBHumj+uoWrBr+k7wFzGIhEYASDrjxkfCKVstDUFXHKGSuFQRndA2cFkkDr4QFGqImsNyzE7jJSCXotvlLTCoiuy0eJOKj1fk5/dKgWRSYKMfRHba7vECgYEAxTK2tLYJT+vNAPy5yt6dV2GClkFzd6z9FP0JgzLV0Gnl8jZldyNnc+OsGjspNzTHIUKbLxip6RPSsXxOpUl8dIgNoKpU00CwSJanZ+7odAzH4JbBrvSlR+ngzf1Tq1awDsmW/M7SDmI7KNeBVsk1bQlCWL4tgrqv1aqyIeoVGpcCgYEAm/0dfM6wQQxwiebDL9giZxfb+y+i3dzoa0t2axx7ZwuRhAOwRaLoPnKRPzWyKbR92ad82scaWeHZ+EwSjIa3mEs3y+O/Fp2DjAaYoS6OvV/agRy3CSyePha0NWhrTqGotz896RevuhF/KVOQCRP7FEkHhxqDZyUTLkG37iAhrYY=',
        // Alipay公钥,在支付宝开发中心由开发者私钥生成
        'rsa_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgGy5Kg5KjGyUr8QdJBdOATVUkE5Zre22MEN81Lsf2sFgusmW9cdHznDsGsauxiWcHhtbM27I1cr3zhgAATRjdZwHdhZWuA6iSlayHk7UPpEFba/flTCrmrZ70ESYMfT9d329LWG4sVcUZo2FuTtMLxDqYa9AgO0HNkKeqhZex48E2ipEG5AOBx+mry8l9qNQapq0JC6JAlSqLT3xpWVXhskAwd/aP1nxxbpUlBfxU+CfG/E9ve5Pni8Uo4bU8sb1vh38yhIYc0JZmMr31mvHogYHkS28F8H0WQqN3fye01kfJvGALgRKUVeow89xakyEUXmJXIvKwPFQEQ27QEnf8QIDAQAB',
        // 异步通知URL
        'notify_url' => config('app.app_host').'/paymentAlipayNotify',
        // 2.0.0版本异步通知URL
        'notify_url_v2_0_0' => config('app.app_host').'/user/v2_0_0/paymentAlipayNotify',
        // 3.0.0版本异步通知URL
        'notify_url_v3_0_0' => config('app.app_host').'/user/v3_0_0/alipayNotify',
        // 3.7.0版本异步通知URL
        'notify_url_v3_7_0' => config('app.app_host').'/user/v3_7_0/alipayNotify',
    ],
];
