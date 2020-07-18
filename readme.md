# Latinum

App for logging acquisitions, including purchaes and gifts, that posts to a server using the ActivityPub client-to-server protocol, ish.

## Run

```
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  composer install
```

## Vocab

Uses terms from the AS2 extension namespace `https://terms.rhiaro.co.uk/as#`, prefix `asext`.

Posts objects with type `as:Article` or `asext:Acquire` (.

## Todo

* [ ] Fetch posts from endpoint to edit
* [ ] Configure source for images