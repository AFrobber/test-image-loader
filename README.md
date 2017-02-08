# TestImageLoader
Test task for Hexa LLC

Для установки с помощью composer файле composer.json в разделах require и repositories (если нет - создать) прописать следующее:

        "require": {
        ...
                "AFrobber/test-image-loader":"*@dev"
        },
        ...
        "repositories": [
                { "type": "git", "url": "https://github.com/AFrobber/test-image-loader.git" }
        ]


и запустить composer update



Пример использования:

        use afrobber\TestImageLoader\TestImageLoader;
        ...
            public function test()
            {
                try {

                    $dir = base_path('storage'.DIRECTORY_SEPARATOR.'testUpload');

                    $c = new TestImageLoader($dir);

                    $url = 'http://i3.i.ua/logo_new1.png';

                    $url2 = 'http://i3.i.ua/logo_new2.png'; //файла не существует

                    if(!$c->uploadFile($url)) {
                        var_dump($c->getErrors());
                    }

                    if(!$c->uploadFile($url2)) {
                        var_dump($c->getErrors());
                    }

                } catch (\Exception $e) {
                    echo $e->getMessage();
                }
            }


