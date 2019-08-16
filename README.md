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
```
./bin/deploy.sh production lib.artnarodnaya.ru
```
