![logo-duel-commander-transparent.png](logo-duel-commander-transparent.png)

# Aeonshift Points calculator

## PHP + JavaScript points calculator for Aeonshift

This project uses vanilla JavaScript and Symfony/PHP.

### Setting up locally

Just do 
```bash
composer install
``` 
...to install dependencies.

Then you should set up your `.env.local` file based on the `.env` template.

And create the database with:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

Then install assets using:
```bash
php bin/console assets:install
php bin/console importmap:install

php bin/console tailwind:build # production, static assets
# or
php bin/console tailwind:build --watch --poll # development, dynamic assets, live reload from file changes
```

Then you can run a local PHP server with:
```bash
symfony server:start -d
```
...or use Docker and build this inside a container.

You can access the application at `http://localhost:8000` (or another port if 8000 is already used).

### Principles

This application has a backend in PHP/Symfony, and a frontend in vanilla JavaScript.
Admins should connect to the backend to do some maintenance tasks:
- Get the file from Scryfall (the app is only provided with a Scryfall file parser, you'll need to create yours if you use another source of data for cards).
- Import the Scryfall data from the downloaded local file into the database for cards.
- Create a release when new rules are published, using a points calculator model in case it would change.

### Deployment

Feel free to edit and reuse the `production-deployment.sh` script to deploy your own instance of this application.
This script is intended to be used on a platform that doesn't provide Node runtime.

Don't forget to also set up your local `.env.local` file on the production server.

### Usage

To have stuff displayed on the frontend, you need to:
1. Import Scryfall bulk file for cards.
2. Parse the cards from the Scryfall file into the database.
3. Create a release for the points calculator, choosing a model.

Steps 1 and 2 need to be done each time you want to update the card database.
They can be automated. Using a cron job is a good idea, calling the console commands:
```bash
 php bin/console as:importfile:scryfalldefault # to download the Scryfall file
 php bin/console as:updatedb:scryfalldefault # to update from the Scryfall file if present
```

(remember doing this too often will have your server banned if abusive, make sure it is done reasonably).

This software is distributed under the MIT License.
Feel free to use it as much as you can, and to contribute to it, PRs are welcome!

Original code from [William Pinaud (DocFX)](https://github.com/DocFX) for Aeonshift, 2025.
