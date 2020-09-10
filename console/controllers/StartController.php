<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 14:18
 */

namespace console\controllers;

use common\helpers\microservices\Authorization\Rules;
use common\helpers\microservices\AuthorizationMethod;
use common\helpers\microservices\PanelMethod;
use console\helpers\MicroserviceHelper;
use console\migrations\microservices\Seeder;
use console\modules\rpc\components\Request;
use console\modules\rpc\components\Response;
use Kakadu\Microservices\exceptions\MicroserviceException;
use Kakadu\Microservices\Microservice;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * Class    StartController
 *
 * @package console\controllers
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class StartController extends Controller
{
    /**
     * Start microservice
     *
     * @return void
     * @throws \Exception
     */
    public function actionIndex(): void
    {
        MicroserviceHelper::init();

        Yii::$app->setComponents([
            // Remove after RBAC deleted
            'request'  => [
                'class' => Request::class,
            ],
            'response' => [
                'class' => Response::class,
            ],
        ]);

        Microservice::getInstance()->start(function ($method, $params) {
            $route = str_replace('.', '/', $method);

            // Set request data
            Yii::$app->request->setRequestData($params);

            return Yii::$app->runAction($route, $params);
        });
    }

    /**
     * Configure microservice
     *
     * @return void
     */
    public function actionConfigure(): void
    {
        MicroserviceHelper::createMicroservice();

        [
            'serviceName' => $serviceName,
        ] = Yii::$app->params;

        $project = $this->getProject();

        $mainLocal   = [];
        $paramsLocal = [];

        // Require project local config if exist
        $projectMainConfig   = Yii::getAlias("@common/config/projects/$projectId/main.php");
        $projectParamsConfig = Yii::getAlias("@common/config/projects/$projectId/params.php");
        $this->mergeProjectConfig($mainLocal, $projectMainConfig);
        $this->mergeProjectConfig($paramsLocal, $projectParamsConfig);

        if (empty($mainLocal['name'])) {
            $mainLocal['name'] = $project['title'];
        }

        // For start app
        $paramsLocal['projectId'] = $projectId;
        $paramsLocal['domain']    = $project['domain'];

        // Set project id for console
        $mainLocal['components']['project']['configProjectId'] = $projectId;

        if ($mysql = $this->getRemoteConfig($project, 'MysqlCredentials.0', 'projectId')) {
            $mainLocal = ArrayHelper::merge($mainLocal, [
                'components' => [
                    'db' => [
                        'host'     => $mysql['host'],
                        'port'     => $mysql['port'],
                        'database' => $mysql['database'] ?? $serviceName,
                        'username' => $mysql['user'],
                        'password' => $mysql['password'] ? : null,
                        'srv'      => $mysql['srv'] ?? null,
                    ],
                ],
            ]);

            if ($slaves = $mysql['slaves'] ?? null) {
                foreach ($slaves as $slave) {
                    $mainLocal['components']['db']['slavesBalancers'][] = $slave['host'];
                }

                $mainLocal['components']['db']['slaveConfig']['username'] = $mysql['user'];
                $mainLocal['components']['db']['slaveConfig']['password'] = $mysql['password'];
            }
        }

        $this->exportConfig('main-local', $this->filterConfig($mainLocal));
        $this->exportConfig('params-local', $this->filterConfig($paramsLocal));

        $this->importAuthorizationRules();
    }

    /**
     * Seeding default data
     */
    public function actionSeeder(): void
    {
        Seeder::run();
    }

    /**
     * Get current project
     *
     * @return array
     * @throws MicroserviceException
     */
    private function getProject(): array
    {
        [
            'projectAlias' => $projectAlias,
            'serviceName'  => $serviceName,
        ] = Yii::$app->params;

        $result = PanelMethod::viewProject([
            'alias' => $projectAlias,
            'query' => [
                'expands' => [
                    [
                        'name'  => 'MysqlCredentials',
                        'where' => [
                            'service' => [
                                'or' => [$serviceName, '*'],
                            ],
                        ],
                        'order' => ['-service'],
                        'limit' => 1,
                    ],
                ],
            ],
        ]);

        if (!$result || empty($result->getResult()['model'] ?? null)) {
            throw new Exception("Project '$projectAlias' not found.");
        }

        return $result->getResult()['model'];
    }

    /**
     * Export microservice configuration
     *
     * @param string $fileName
     * @param array  $config
     *
     * @return void
     */
    private function exportConfig(string $fileName, array $config): void
    {
        $patterns = [
            '/array \(/'                       => '[',
            '/^([ ]*)\)(,?)$/m'                => '$1]$2',
            '/=>[ ]?\n[ ]+\[/'                 => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];

        $content = "<?php \n";
        $content .= 'return ';
        $content .= preg_replace(array_keys($patterns), array_values($patterns), var_export($config, true));
        $content .= ';';

        file_put_contents(Yii::getAlias("@common/config/$fileName.php"), $content);
    }

    /**
     * Get project remote config
     *
     * @param array  $project
     * @param string $location   "test.location.in.array"
     * @param string $checkField "field"
     *
     * @return array|null
     */
    private function getRemoteConfig(array $project, string $location, string $checkField): ?array
    {
        $config = ArrayHelper::getValue($project, $location);

        if (!$config) {
            return null;
        }

        $checkValue = ArrayHelper::getValue($project, "$location.$checkField");

        if (!$checkValue) {
            return null;
        }

        return $config;
    }

    /**
     * Add project local config
     *
     * @param array  $config
     * @param string $path
     *
     * @return void
     */
    private function mergeProjectConfig(array &$config, string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $config = ArrayHelper::merge($config, require $path);
    }

    /**
     * Filter array. Remove null values
     *
     * @param array $config
     *
     * @return array
     */
    private function filterConfig(array $config): array
    {
        $filteredConfig = [];

        foreach ($config as $key => $value) {
            if (is_array($value) && count($value) > 0) {
                $filteredConfig[$key] = $this->filterConfig($value);
            } elseif ($value !== null) {
                $filteredConfig[$key] = $value;
            }
        }

        return $filteredConfig;
    }

    /**
     * Import authorization rules
     *
     * @return void
     * @throws MicroserviceException
     */
    private function importAuthorizationRules(): void
    {
        [
            'serviceName' => $serviceName,
        ] = Yii::$app->params;

        AuthorizationMethod::importRules($serviceName, Rules::VERSION, Rules::rules());
    }
}
