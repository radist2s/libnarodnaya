# Trellist

`cd ./trellis`

### Vault encrypt

```
ansible-vault encrypt group_vars/production/vault.yml
```

### Vault decrypt
```
ansible-vault decrypt group_vars/production/vault.yml
```

### Deploy
Change nod version via `nvm` or install no greater than `node` `v10.16.2`
```
nvm use lts/dubnium

./bin/deploy.sh production lib.artnarodnaya.ru
```
