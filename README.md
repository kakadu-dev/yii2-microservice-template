Yii 2 Inverted JSON Microservice template
---

Run:
1. Init app
      ```bash
      php init
      ```
2. Get project configuration. If needed.
    ```bash
    php yii start/configure
    ```
3. Start microservice
    ```bash
    php yii start
    ```

ENVIRONMENT (pass via shell):
```
IJSON - Inverted JSON host:port
PROJECT_ALIAS - Project alias for microservice
```

Tests:
Import Watcher task from ```.idea/watcherTasks.xml``` for autotests or ```codecept run```
