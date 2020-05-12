<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 14:18
 */

namespace console\controllers;

use common\helpers\microservices\PanelMethod;
use Kakadu\Microservices\exceptions\MicroserviceException;
use Kakadu\Microservices\Microservice;
use Yii;
use yii\console\Controller;

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
        $this->createMicroservice();

        Microservice::getInstance()->start(function ($method, $params) {
            $route = str_replace('.', '/', $method);

            return Yii::$app->runAction($route, $params);
        });
    }

    /**
     * Create microservice instance
     *
     * @throws \Exception
     */
    private function createMicroservice(): void
    {
        [
            'projectAlias' => $projectAlias,
            'serviceName'  => $serviceName,
            'ijsonHost'    => $ijsonHost,
        ] = Yii::$app->params;

        Microservice::create("$projectAlias:$serviceName", [
            'ijson' => $ijsonHost,
            'env'   => YII_ENV,
        ], YII_DEBUG);
    }

    /**
     * Configure microservice
     *
     * @return void
     */
    public function actionConfigure(): void
    {
        $this->createMicroservice();

        [
            'serviceName' => $serviceName,
        ] = Yii::$app->params;

        $project = $this->getProject();

        $mainLocal   = [];
        $paramsLocal = [];

        if ($mysql = $project['MysqlCredentials'][0] ?? null) {
            $mainLocal['components']['db']['host']            = $mysql['host'];
            $mainLocal['components']['db']['port']            = $mysql['port'];
            $mainLocal['components']['db']['database']        = $mysql['database'] ?? $serviceName;
            $mainLocal['components']['db']['username']        = $mysql['user'];
            $mainLocal['components']['db']['password']        = $mysql['password'];
            $mainLocal['components']['db']['srv']             = $mysql['srv'] ?? null;
            $mainLocal['components']['db']['slavesBalancers'] = $mysql['slaves'] ?? null;

            $mainLocal['components']['db']['slaveConfig']['username'] = $mysql['user'];
            $mainLocal['components']['db']['slaveConfig']['password'] = $mysql['password'];
        }

        //        if ($firebaseConfig = $project['FirebaseConfig'] ?? []) {
        //            $mainLocal['']
        //        }

        if ($config = $project['CurentMicroserviceNameConfig'] ?? []) {
            $paramsLocal = array_merge($paramsLocal, $config);
        }

        $this->exportConfig('main-local', $this->filterConfig($mainLocal));
        $this->exportConfig('params-local', $this->filterConfig($paramsLocal));
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
                                '$or' => [$serviceName, '*'],
                            ],
                        ],
                    ],
                    //                    [
                    //                        'name'     => 'FirebaseConfig',
                    //                        'required' => false,
                    //                    ],
                    //                    [
                    //                        'name' => 'CurentMicroserviceNameConfig',
                    //                    ]
                ],
            ],
        ]);

        return $result ? ($result->getResult()['model'] ?? []) : [];
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
            "/array \(/"                       => '[',
            "/^([ ]*)\)(,?)$/m"                => '$1]$2',
            "/=>[ ]?\n[ ]+\[/"                 => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];

        $content = "<?php \n";
        $content .= "return ";
        $content .= preg_replace(array_keys($patterns), array_values($patterns), var_export($config, true));
        $content .= ';';

        file_put_contents(Yii::getAlias("@common/config/$fileName.php"), $content);
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
            } else if ($value !== null) {
                $filteredConfig[$key] = $value;
            }
        }

        return $filteredConfig;
    }
}
