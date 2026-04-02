<?php
/**
 * FuzeWorks Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2019 TechFuze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Core\Core;

return [
    'active_group' => 'pdo',
    'connections' => [
        'pdo' => [
            'engineName' =>     'pdo',
            'dsn' =>            Core::getEnv(
                "PDO_DSN", 
                "mysql:host" . Core::getEnv("PDO_HOST", "localhost").
                ";dbname=" . Core::getEnv("PDO_NAME", "").
                ";charset=" . Core::getEnv("PDO_CHARSET", "utf8")),
            'hostname' =>       Core::getEnv("PDO_HOST", "localhost"),
            'username' =>       Core::getEnv("PDO_USER", ""),
            'password' =>       Core::getEnv("PDO_PASS", ""),
            'database' =>       Core::getEnv("PDO_NAME", ""),
            'prefix' =>         Core::getEnv("PDO_PREFIX", ""),
            'persistent' =>     false,
            'debug' =>          false,
            'charset' =>        Core::getEnv("PDO_CHARSET", "utf8"),
            'collation' =>      Core::getEnv("PDO_COLLATION", "utf8_general_ci")
        ],
        "mongodb" => [
            'engineName' =>     'mongo',
            'uri' =>            Core::getEnv(
                "MONGO_DSN", 
                "mongodb://" . Core::getEnv("MONGO_HOST", "localhost") . ":" . Core::getEnv("MONGO_PORT", "27017")),
            'username' =>       Core::getEnv("MONGO_USER", null),
            'password' =>       Core::getEnv("MONGO_PASS", null),
            'uriOptions' => [],
            'driverOptions' => [],
        ]
    ]
];