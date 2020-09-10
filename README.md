Yii 2 Inverted JSON Microservice template
---

ENVIRONMENT:  
 - `IJSON` - Invert json host and port (with protocol)
 - `PROJECT_ALIAS` - megofon, easysoup, etc..

**Run:**
 - Get code:
    - `git clone git@github.com:kakadu-dev/base-backend.git`

 - Configure (pass environment variables below for each step):
    - `composer install`
    - `php init`
        - Configure if `prod`|`dev` ```php yii start/configure```
        - Configure if local development ```common/config/main-local.php``` etc...
    - Run docker container `ijson` and `mysql` in `docker-compose.yml`
      ```bash
        docker-compose run mysql
        docker-compose run ijson
      ```
    - Init RBAC `php yii rbac/init`
    - Apply migrations `php yii migrate`
    - Start server `php yii start`
    - See `scratches` folder for make requests

Tests:
Import Watcher task from ```.idea/watcherTasks.xml``` for autotests or ```codecept run```
