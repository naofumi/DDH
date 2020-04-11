#SETUP

## MAMP

### Yosemite

If you upgraded to Yosemite and MAMP isn't working, you have to do [this](http://stackoverflow.com/questions/25201280/apache-not-starting-on-mamp-pro/25212463#25212463);

> You apparently have to go to your MAMP folder in Applications. Go to bin -> apache2 -> bin:

> Then change envvars to _envvars.

This solution looks better though.

### Installing MongoDB using MacPorts

I chose to use MacPorts but I ultimately failed because I couldn't install the `scons` dependency. 

Upgrade MacPorts for Yosemite
http://d.hatena.ne.jp/pyopyopyo/20140816/p1

`sudo port upgrade` to upgrade the ports.

We might need to  do a more through update following these instructions;

[Migrating a MacPorts install to a new major OS version or CPU architecture](https://trac.macports.org/wiki/Migration)

### Install MongoDB using homebrew

Reinstall HomeBrew

`NaoAir:~ nao$ ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"`

`brew install mongodb`
This installed the `scons` that MacPorts and finished successfully.

### Installing the mongodb module into PHP-MAMP

[http://lukepeters.me/blog/setting-up-mongodb-with-php-and-mamp](http://lukepeters.me/blog/setting-up-mongodb-with-php-and-mamp)

I needed to download the headers as in "Fixing the 'make' failed error"