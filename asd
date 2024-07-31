[1mdiff --git a/.history/composer_20240727071044.json b/.history/composer_20240727071044.json[m
[1mnew file mode 100644[m
[1mindex 0000000..6740ec0[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727071044.json[m
[36m@@ -0,0 +1,70 @@[m
[32m+[m[32m{[m
[32m+[m[32m    "name": "laravel/laravel",[m
[32m+[m[32m    "type": "project",[m
[32m+[m[32m    "description": "The skeleton application for the Laravel framework.",[m
[32m+[m[32m    "keywords": ["laravel", "framework"],[m
[32m+[m[32m    "license": "MIT",[m
[32m+[m[32m    "require": {[m
[32m+[m[32m        "php": "^8.1",[m
[32m+[m[32m        "barryvdh/laravel-dompdf": "^2.2",[m
[32m+[m[32m        "guzzlehttp/guzzle": "^7.2",[m
[32m+[m[32m        "laravel/framework": "^10.10",[m
[32m+[m[32m        "laravel/passport": "^12.1",[m
[32m+[m[32m        "laravel/sanctum": "^3.3",[m
[32m+[m[32m        "laravel/tinker": "^2.8",[m
[32m+[m[32m        "stripe/stripe-php": "^14.5"[m
[32m+[m[32m    },[m
[32m+[m[32m    "require-dev": {[m
[32m+[m[32m        "fakerphp/faker": "^1.9.1",[m
[32m+[m[32m        "laravel/pint": "^1.0",[m
[32m+[m[32m        "laravel/sail": "^1.18",[m
[32m+[m
[32m+[m[32m        "mockery/mockery": "^1.4.4",[m
[32m+[m[32m        "nunomaduro/collision": "^7.0",[m
[32m+[m[32m        "phpunit/phpunit": "^10.1",[m
[32m+[m[32m        "spatie/laravel-ignition": "^2.0"[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "App\\": "app/",[m
[32m+[m[32m            "Database\\Factories\\": "database/factories/",[m
[32m+[m[32m            "Database\\Seeders\\": "database/seeders/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload-dev": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "Tests\\": "tests/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "post-autoload-dump": [[m
[32m+[m[32m            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",[m
[32m+[m[32m            "@php artisan package:discover --ansi"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-update-cmd": [[m
[32m+[m[32m            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-root-package-install": [[m
[32m+[m[32m            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-create-project-cmd": [[m
[32m+[m[32m            "@php artisan key:generate --ansi"[m
[32m+[m[32m        ][m
[32m+[m[32m    },[m
[32m+[m[32m    "extra": {[m
[32m+[m[32m        "laravel": {[m
[32m+[m[32m            "dont-discover": [][m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "config": {[m
[32m+[m[32m        "optimize-autoloader": true,[m
[32m+[m[32m        "preferred-install": "dist",[m
[32m+[m[32m        "sort-packages": true,[m
[32m+[m[32m        "allow-plugins": {[m
[32m+[m[32m            "pestphp/pest-plugin": true,[m
[32m+[m[32m            "php-http/discovery": true[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "minimum-stability": "stable",[m
[32m+[m[32m    "prefer-stable": true[m
[32m+[m[32m}[m
[1mdiff --git a/.history/composer_20240727071218.json b/.history/composer_20240727071218.json[m
[1mnew file mode 100644[m
[1mindex 0000000..5c4ff1c[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727071218.json[m
[36m@@ -0,0 +1 @@[m
[32m+[m[32mcomposer clear-cache[m
[1mdiff --git a/.history/composer_20240727071219.json b/.history/composer_20240727071219.json[m
[1mnew file mode 100644[m
[1mindex 0000000..6740ec0[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727071219.json[m
[36m@@ -0,0 +1,70 @@[m
[32m+[m[32m{[m
[32m+[m[32m    "name": "laravel/laravel",[m
[32m+[m[32m    "type": "project",[m
[32m+[m[32m    "description": "The skeleton application for the Laravel framework.",[m
[32m+[m[32m    "keywords": ["laravel", "framework"],[m
[32m+[m[32m    "license": "MIT",[m
[32m+[m[32m    "require": {[m
[32m+[m[32m        "php": "^8.1",[m
[32m+[m[32m        "barryvdh/laravel-dompdf": "^2.2",[m
[32m+[m[32m        "guzzlehttp/guzzle": "^7.2",[m
[32m+[m[32m        "laravel/framework": "^10.10",[m
[32m+[m[32m        "laravel/passport": "^12.1",[m
[32m+[m[32m        "laravel/sanctum": "^3.3",[m
[32m+[m[32m        "laravel/tinker": "^2.8",[m
[32m+[m[32m        "stripe/stripe-php": "^14.5"[m
[32m+[m[32m    },[m
[32m+[m[32m    "require-dev": {[m
[32m+[m[32m        "fakerphp/faker": "^1.9.1",[m
[32m+[m[32m        "laravel/pint": "^1.0",[m
[32m+[m[32m        "laravel/sail": "^1.18",[m
[32m+[m
[32m+[m[32m        "mockery/mockery": "^1.4.4",[m
[32m+[m[32m        "nunomaduro/collision": "^7.0",[m
[32m+[m[32m        "phpunit/phpunit": "^10.1",[m
[32m+[m[32m        "spatie/laravel-ignition": "^2.0"[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "App\\": "app/",[m
[32m+[m[32m            "Database\\Factories\\": "database/factories/",[m
[32m+[m[32m            "Database\\Seeders\\": "database/seeders/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload-dev": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "Tests\\": "tests/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "post-autoload-dump": [[m
[32m+[m[32m            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",[m
[32m+[m[32m            "@php artisan package:discover --ansi"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-update-cmd": [[m
[32m+[m[32m            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-root-package-install": [[m
[32m+[m[32m            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-create-project-cmd": [[m
[32m+[m[32m            "@php artisan key:generate --ansi"[m
[32m+[m[32m        ][m
[32m+[m[32m    },[m
[32m+[m[32m    "extra": {[m
[32m+[m[32m        "laravel": {[m
[32m+[m[32m            "dont-discover": [][m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "config": {[m
[32m+[m[32m        "optimize-autoloader": true,[m
[32m+[m[32m        "preferred-install": "dist",[m
[32m+[m[32m        "sort-packages": true,[m
[32m+[m[32m        "allow-plugins": {[m
[32m+[m[32m            "pestphp/pest-plugin": true,[m
[32m+[m[32m            "php-http/discovery": true[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "minimum-stability": "stable",[m
[32m+[m[32m    "prefer-stable": true[m
[32m+[m[32m}[m
[1mdiff --git a/.history/composer_20240727141643.json b/.history/composer_20240727141643.json[m
[1mnew file mode 100644[m
[1mindex 0000000..207e315[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727141643.json[m
[36m@@ -0,0 +1,71 @@[m
[32m+[m[32m{[m
[32m+[m[32m    "name": "laravel/laravel",[m
[32m+[m[32m    "type": "project",[m
[32m+[m[32m    "description": "The skeleton application for the Laravel framework.",[m
[32m+[m[32m    "keywords": ["laravel", "framework"],[m
[32m+[m[32m    "license": "MIT",[m
[32m+[m[32m    "require": {[m
[32m+[m[32m        "php": "^8.1",[m
[32m+[m[32m        "barryvdh/laravel-dompdf": "^2.2",[m
[32m+[m[32m        "guzzlehttp/guzzle": "^7.2",[m
[32m+[m[32m        "fruitcake/laravel-cors": "^2.2",[m
[32m+[m[32m        "laravel/framework": "^10.10",[m
[32m+[m[32m        "laravel/passport": "^12.1",[m
[32m+[m[32m        "laravel/sanctum": "^3.3",[m
[32m+[m[32m        "laravel/tinker": "^2.8",[m
[32m+[m[32m        "stripe/stripe-php": "^14.5"[m
[32m+[m[32m    },[m
[32m+[m[32m    "require-dev": {[m
[32m+[m[32m        "fakerphp/faker": "^1.9.1",[m
[32m+[m[32m        "laravel/pint": "^1.0",[m
[32m+[m[32m        "laravel/sail": "^1.18",[m
[32m+[m
[32m+[m[32m        "mockery/mockery": "^1.4.4",[m
[32m+[m[32m        "nunomaduro/collision": "^7.0",[m
[32m+[m[32m        "phpunit/phpunit": "^10.1",[m
[32m+[m[32m        "spatie/laravel-ignition": "^2.0"[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "App\\": "app/",[m
[32m+[m[32m            "Database\\Factories\\": "database/factories/",[m
[32m+[m[32m            "Database\\Seeders\\": "database/seeders/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload-dev": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "Tests\\": "tests/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "post-autoload-dump": [[m
[32m+[m[32m            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",[m
[32m+[m[32m            "@php artisan package:discover --ansi"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-update-cmd": [[m
[32m+[m[32m            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-root-package-install": [[m
[32m+[m[32m            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-create-project-cmd": [[m
[32m+[m[32m            "@php artisan key:generate --ansi"[m
[32m+[m[32m        ][m
[32m+[m[32m    },[m
[32m+[m[32m    "extra": {[m
[32m+[m[32m        "laravel": {[m
[32m+[m[32m            "dont-discover": [][m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "config": {[m
[32m+[m[32m        "optimize-autoloader": true,[m
[32m+[m[32m        "preferred-install": "dist",[m
[32m+[m[32m        "sort-packages": true,[m
[32m+[m[32m        "allow-plugins": {[m
[32m+[m[32m            "pestphp/pest-plugin": true,[m
[32m+[m[32m            "php-http/discovery": true[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "minimum-stability": "stable",[m
[32m+[m[32m    "prefer-stable": true[m
[32m+[m[32m}[m
[1mdiff --git a/.history/composer_20240727141807.json b/.history/composer_20240727141807.json[m
[1mnew file mode 100644[m
[1mindex 0000000..b4b4ba5[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727141807.json[m
[36m@@ -0,0 +1,71 @@[m
[32m+[m[32m{[m
[32m+[m[32m    "name": "laravel/laravel",[m
[32m+[m[32m    "type": "project",[m
[32m+[m[32m    "description": "The skeleton application for the Laravel framework.",[m
[32m+[m[32m    "keywords": ["laravel", "framework"],[m
[32m+[m[32m    "license": "MIT",[m
[32m+[m[32m    "require": {[m
[32m+[m[32m        "php": "^8.1",[m
[32m+[m[32m        "barryvdh/laravel-dompdf": "^2.2",[m
[32m+[m[32m        "guzzlehttp/guzzle": "^7.2",[m
[32m+[m[32m        "fruitcake/laravel-cors": "^*",[m
[32m+[m[32m        "laravel/framework": "^10.10",[m
[32m+[m[32m        "laravel/passport": "^12.1",[m
[32m+[m[32m        "laravel/sanctum": "^3.3",[m
[32m+[m[32m        "laravel/tinker": "^2.8",[m
[32m+[m[32m        "stripe/stripe-php": "^14.5"[m
[32m+[m[32m    },[m
[32m+[m[32m    "require-dev": {[m
[32m+[m[32m        "fakerphp/faker": "^1.9.1",[m
[32m+[m[32m        "laravel/pint": "^1.0",[m
[32m+[m[32m        "laravel/sail": "^1.18",[m
[32m+[m
[32m+[m[32m        "mockery/mockery": "^1.4.4",[m
[32m+[m[32m        "nunomaduro/collision": "^7.0",[m
[32m+[m[32m        "phpunit/phpunit": "^10.1",[m
[32m+[m[32m        "spatie/laravel-ignition": "^2.0"[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "App\\": "app/",[m
[32m+[m[32m            "Database\\Factories\\": "database/factories/",[m
[32m+[m[32m            "Database\\Seeders\\": "database/seeders/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload-dev": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "Tests\\": "tests/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "post-autoload-dump": [[m
[32m+[m[32m            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",[m
[32m+[m[32m            "@php artisan package:discover --ansi"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-update-cmd": [[m
[32m+[m[32m            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-root-package-install": [[m
[32m+[m[32m            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""[m
[32m+[m[32m        ],[m
[32m+[m[32m        "post-create-project-cmd": [[m
[32m+[m[32m            "@php artisan key:generate --ansi"[m
[32m+[m[32m        ][m
[32m+[m[32m    },[m
[32m+[m[32m    "extra": {[m
[32m+[m[32m        "laravel": {[m
[32m+[m[32m            "dont-discover": [][m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "config": {[m
[32m+[m[32m        "optimize-autoloader": true,[m
[32m+[m[32m        "preferred-install": "dist",[m
[32m+[m[32m        "sort-packages": true,[m
[32m+[m[32m        "allow-plugins": {[m
[32m+[m[32m            "pestphp/pest-plugin": true,[m
[32m+[m[32m            "php-http/discovery": true[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "minimum-stability": "stable",[m
[32m+[m[32m    "prefer-stable": true[m
[32m+[m[32m}[m
[1mdiff --git a/.history/composer_20240727141827.json b/.history/composer_20240727141827.json[m
[1mnew file mode 100644[m
[1mindex 0000000..6870952[m
[1m--- /dev/null[m
[1m+++ b/.history/composer_20240727141827.json[m
[36m@@ -0,0 +1,71 @@[m
[32m+[m[32m{[m
[32m+[m[32m    "name": "laravel/laravel",[m
[32m+[m[32m    "type": "project",[m
[32m+[m[32m    "description": "The skeleton application for the Laravel framework.",[m
[32m+[m[32m    "keywords": ["laravel", "framework"],[m
[32m+[m[32m    "license": "MIT",[m
[32m+[m[32m    "require": {[m
[32m+[m[32m        "php": "^8.1",[m
[32m+[m[32m        "barryvdh/laravel-dompdf": "^2.2",[m
[32m+[m[32m        "guzzlehttp/guzzle": "^7.2",[m
[32m+[m[32m        "fruitcake/laravel-cors": "*",[m
[32m+[m[32m        "laravel/framework": "^10.10",[m
[32m+[m[32m        "laravel/passport": "^12.1",[m
[32m+[m[32m        "laravel/sanctum": "^3.3",[m
[32m+[m[32m        "laravel/tinker": "^2.8",[m
[32m+[m[32m        "stripe/stripe-php": "^14.5"[m
[32m+[m[32m    },[m
[32m+[m[32m    "require-dev": {[m
[32m+[m[32m        "fakerphp/faker": "^1.9.1",[m
[32m+[m[32m        "laravel/pint": "^1.0",[m
[32m+[m[32m        "laravel/sail": "^1.18",[m
[32m+[m
[32m+[m[32m        "mockery/mockery": "^1.4.4",[m
[32m+[m[32m        "nunomaduro/collision": "^7.0",[m
[32m+[m[32m        "phpunit/phpunit": "^10.1",[m
[32m+[m[32m        "spatie/laravel-ignition": "^2.0"[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "App\\": "app/",[m
[32m+[m[32m            "Database\\Factories\\": "database/factories/",[m
[32m+[m[32m            "Database\\Seeders\\": "database/seeders/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "autoload-dev": {[m
[32m+[m[32m        "psr-4": {[m
[32m+[m[32m            "Tests\\": "tests/"[m
[32m+[m[32m        }[m
[32m+[m[32m    },[m
[32m+[m[32m    "scripts": {[m
[32m+[m[32m        "post-autoload-dump": [[m
[32m+[m[32m            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",[m
[32m+[m[32m            "@php artisan 