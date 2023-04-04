<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\CacheManager\Console\Cache;

use Weline\Framework\App\Env;
use Weline\Framework\Cache\CacheInterface;
use Weline\Framework\Cache\Scanner;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;

class Status implements \Weline\Framework\Console\CommandInterface
{
    /**
     * @var Scanner
     */
    private Scanner $scanner;
    private Printing $printing;

    public function __construct(
        Scanner  $scanner,
        Printing $printing
    )
    {
        $this->scanner  = $scanner;
        $this->printing = $printing;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = [], array $data = [])
    {
        # 操作符
        if (isset($args[1]) && $op = $args[1]) {
            $caches = array_merge($this->scanner->scanFrameworkCaches(), $this->scanner->scanAppCaches());
            foreach ($caches as &$cache) {
                $cache = ObjectManager::getInstance((rtrim($cache['class'], 'Factory') . 'Factory'));
            }
            /**@var CacheInterface $cacheObj */
            switch ($op) {
                case 'enable':
                case 'disable':
                    $status       = $op == 'enable' ? 1 : 0;
                    $cache_config = Env::getInstance()->getData('cache');
                    $identify_s   = array_slice($args, 2, count($args));
                    $no_has_data  = [];
                    $set_data     = $cache_config['status'] ?? [];
                    if ($identify_s) {
                        foreach ($identify_s as $identify) {
                            $no_has = true;
                            foreach ($caches as $cacheObj) {
                                if ($identify === $cacheObj->getIdentify()) {
                                    $set_data[$identify] = $status;
                                    $no_has              = false;
                                }
                            }
                            if ($no_has) {
                                $no_has_data[] = $identify;
                            }
                        }
                        # 配置缓存
                        $cache_config['status'] = $set_data;
                        Env::getInstance()->setConfig('cache', $cache_config);
                        $this->printAll();
                        if ($no_has_data) {
                            $this->printing->error(__('不存在的缓存标识：'));
                            $this->printing->printList($no_has_data);
                        }
                    } else {
                        foreach ($caches as $cacheObj) {
                            $identify            = $cacheObj->getIdentify();
                            $set_data[$identify] = $status;
                        }
                        $cache_config['status'] = $set_data;
                        Env::getInstance()->setConfig('cache', $cache_config);
//                            ObjectManager::getInstance(Clear::class)->execute(['-f']);
                        $this->printAll();
                    }
                    break;
                default:
                    $this->printing->error(__('错误的操作,正确示例：%1', 'php bin/m cache:status [enable/disable] [identify...]'));
            }
        } else {
            # 处理缓存状态默认查看 所有缓存状态
            $this->printAll();
        }
    }

    public function printAll()
    {
        $this->printing->warning(__('模组缓存'));
        $caches = $this->scanner->scanAppCaches();
        /**@var CacheInterface $cache */
        foreach ($caches as $cache_data) {
            $cache = ObjectManager::make(rtrim($cache_data['class'], 'Factory') . 'Factory');
            $this->printing->note(
                str_pad($cache->getIdentify(), 45) .
                '=>' . ($cache->getStatus() ? 1 : 0) . '   ' . $cache->tip()
            );
        }
        $this->printing->warning(__('框架缓存'));
        $caches = $this->scanner->scanFrameworkCaches();
        /**@var CacheInterface $cache */
        foreach ($caches as $cache_data) {
            $cache = ObjectManager::getInstance(rtrim($cache_data['class'], 'Factory') . 'Factory');
            $this->printing->note(
                str_pad($cache->getIdentify(), 45) .
                '=>' . ($cache->getStatus() ? 1 : 0) . '   ' . $cache->tip()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function tip(): string
    {
        return __('缓存状态。[enable/disable]:开启/关闭 [identify...]:缓存识别名');
    }
}
