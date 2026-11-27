module.exports = {
  apps: [
    {
      name: "unidata-web",
      script: "artisan",
      interpreter: "php",
      args: "serve --host=0.0.0.0 --port=8000",
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: "512M",
      env: {
        APP_ENV: "production",
        PHP_CLI_SERVER_WORKERS: 4
      }
    },
    {
      name: "unidata-queue",
      script: "artisan",
      interpreter: "php",
      args: "queue:work --tries=3 --timeout=300",
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: "512M"
    }
  ]
};

