This is a Symfony project where users can register for events. There is no UI, but by default it can be tested with the Nelmio bundle at the /api/doc URL. I mainly used HTTPie, because Swagger unfortunately cannot handle operations that require authentication when it comes to session cookies. To help with testing, there is also a simple factory and seeder, which can be run with the command: php bin/console foundry:load-fixtures main


