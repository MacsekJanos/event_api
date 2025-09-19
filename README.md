This is a Symfony project for event registrations, exposed as an API only with no dedicated UI.
Interactive documentation is available via NelmioApiDoc at the /api/doc
 route, configured with Swagger UI.
For authenticated endpoints using session cookies, Swagger UI’s Try it out cannot perform requests due to browser restrictions, so HTTPie was used for testing instead.
To seed test data, a lightweight Foundry factory and seeder are included and can be executed with: php bin/console foundry:load-fixtures main
