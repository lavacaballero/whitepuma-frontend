# WhitePuma Open Source Platform - User Frontend

### Foreword from the original author

This project born back in early 2014 on /r/dogecoin. Tipping on Facebook was the topic,
but there were no devs on it. So I launched a proposal on making it like a platform and
not as a stand-alone bot. I gave some tips but, still, no dev was taking the lead.

Then I jumped in and got started. By February 11 I got the Dogecoin Tipping App started.
Then, a month later, the same source on a second app for multiple coins: Doge, Redd, Fedora
and Digibyte.
Then, I got the Doge app officially approved by Facebook. A couple of weeks afterwards,
got the Multicoin App approved.
Then I got it to Twitter, and then to any website with an embeddble script.
Then to Instagram.
But I wasn't releasing the source because it had some flaws that weren't good for anyone.

So I finally put my hands on the code, cleaned up some of the garbage and release it.
Now here you have it (~_~)

### What is it for?

**Cryptocurrencies.** Like in "Bitcoin". This platform has components that allow the users to
send/receive coins from/to an online wallet through different channels, including
Facebook and Twitter.

### Components

* **Multiple coins supported:** You can setup the platform to support one or many
  coins.

* **Facebook tipping:** Facebook canvas pages and "Pulse" utility to process tipping
  commands. Includes support for Facebook login and alternate email login info.
  
* **Tipping rains:** Facebook groups based utility to spread coins to random
  recipients from any user.
  
* **Twitter tipping:** Login support and stream monitor for tipping using the same
  command syntaxes.

* **Instagram tipping:** Rudimentary tipping on Instagram through hashtag monitoring.
  (Instagram's API is far less permissive than Facebook's). Includes login support.
  
* **Universal widget:** HTML/Javascript component for embedding on websites. Any
  website can be enabled to render tip/payment buttons by adding a JS snippet on the
  HTML ‹head› tag and buttons are rendered from simple ‹a› tags.

* **Ticker:** Using [Cryptonator's API](https://www.cryptonator.com/api), coin
  balances in USD can be shown on the dashboard.

### Infrastructure and security

This platform is made in three layers:

1. **Frontend:** (here) where the users generate addresses, check balances and transactions.
   Tipping and sending apps live here and nowhere else, but no coins are stored in here.
   Wallet requests are made to the next layer using a standard POST request over HTTP
   with a custom data encryption algorithm, so no SSL is required.
   
2. **Backend:** a kind of reverse proxy to protect the wallets. It communicates with the
   wallets endpoint using the same method as the frontend, but with different encryption
   keys. The hostname or IP address of this host is not visible to the end users.
   
3. **Wallets endpoint:** an API that receives the requests from the backend and connects
   with the wallet daemon through JSON RPC, process data and returns it to the backend.
   The host name or IP address of this host is not visible to the end users.
   
As you may have noticed, the scheme requires three different hosts:

1. The Frontend could be any shared web hosting plan, as long as you can add cron jobs
   to it. For increased security, a small VPS should be used, and the PHP files should
   be encrypted with any commercial encoding app like [IonCube](http://www.ioncube.com/).
   If this host is compromised, the hacker will need to get into the next layer.
   
2. The backend could be a really small VPS, since it will only be listening to the
   frontend scripts. Script encryption is also sugested here.
   If this host is compromised, the hacker will need to get into the next layer.
   
3. The wallets endpoint will have no database, so it will require less resources.
   If this host is compromised, your wallet will be safe if the configuration file
   from the endpoint's API is encrypted.
   
You may use a single server to keep the three layers, but for around US$30/mo. you
can have a very good security scheme on the service.

### The code

You'll find a lot of spaghetti here, basically because I planned the security before
anything else and... well, I didn't plan the rest. It needs a lot of work, but you'll
find a lot of love in here.

### More information

* [Platform Backend](https://github.com/lavacaballero/whitepuma-backend):
  the "man in the middle" to keep the wallets far from reach.
  
* [Wallets endpoint](https://github.com/lavacaballero/whitepuma-wallets):
  the keeper of your coins.

## Requirements

* Apache 2 with rewrite module
* PHP 5.3+ with mcrypt, curl, zlib and gd
* MySQL 5.5+

## Installation

Assuming you already setup Apache, PHP and MySQL and you have a host name for the platform:

1. Setup the backend host and the wallet endpoint.
   **Note:** If you've configured Apache yourself, you must configure the *AllowOverride* directive
   to **all** on the vhost section.

2. Create a user, a pass and a database to hold the data cache.

3. Rename the `config-sample.php` file to `config.php` and customize it.
   You should pay attention to the variables you're setting, since they must match
   the ones you've set on the backend's config file.
   **Note:** customization of this file will take you hours. You will need to setup
   some imagery and provide access tokens to those social networks you will connect to.
   Plus, you will have to think on different component names for a fully customized
   version of the platform.

4. Rename the `.htaccess-sample` file to `.htaccess` and customize the 
   universal widget paths.
   
5. Upload these scripts to the document root.
 
6. Integrate the `DB_TABLES.sql` file on the database.

7. Check the `crontab.txt` file and add it to your existing cron jobs.

8. Make sure everything is running and open your browser to your newly installed website.
   Create an account and you'll get your wallet address created!

## Usage

All inside the website is self-descriptive, but you can always go to http://www.whitepuma.net and
create an account with any of the apps to see how it works.

## Contributing

**Maintainers are needed!** If you want to maintain this repository, feel free to let me know.
I don't have enough time to attend pull requests, so you're invited to be part of the core staff
behind this source.

## Credits

Author: Alejandro Caballero - acaballero@lavasoftworks.com

## License

This project is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This project is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
