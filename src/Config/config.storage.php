<?php
/**
 * FuzeWorks ObjectStorage Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2020 i15
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
 * @author    i15
 * @copyright Copyright (c) 2013 - 2020, i15. (https://i15.nl)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @since Version 1.3.0
 *
 * @version Version 1.3.0
 */

use FuzeWorks\Core\Core;

return [
    // Which provider shall be used
    // Options: DummyProvider, RedisProvider, FileProvider
    'StorageProvider' => Core::getEnv('STORAGE_PROVIDER', null),
    'DummyProvider' => [],
    'RedisProvider' => [
        // Type can be 'tcp' or 'unix'
        'socket_type' => Core::getEnv('STORAGE_REDIS_SOCKET_TYPE', 'tcp'),
        // If socket_type == 'unix', set the socket here
        'socket' => Core::getEnv('STORAGE_REDIS_SOCKET', null),
        // If socket_type == 'tcp', set the host here
        'host' => Core::getEnv('STORAGE_REDIS_HOST', '127.0.0.1'),
        // And some standard settings
        'port' => Core::getEnv('STORAGE_REDIS_PORT', 6379),
        'password' => Core::getEnv('STORAGE_REDIS_PASSWORD', null),
        'timeout' => Core::getEnv('STORAGE_REDIS_TIMEOUT', 0),
        'db_index' => Core::getEnv('STORAGE_REDIS_DBINDEX', 0),
    ],
    'FileProvider' => [
        // The directory where objects get stored by the FileProvider
        'storage_directory' => Core::getEnv('STORAGE_FILE_DIRECTORY', Core::$tempDir)
    ]
];