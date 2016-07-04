Primeiros passos
----------------

A grande maioria dos recursos disponibilizados são restringidos para tipos de
usuarios especificos. Portanto, para ter acesso a um determinado
recurso protegido primeiro você deve ter um usuário com as devidas
permisões para o recurso.

Por exemplo para requisitar a lista de
[rotas disponíveis](http://localhost/BusTrackerAPI/docs/index.html#api-Routes-GetRoutes)
devemos ter um usuário com permissão `client`.
Assim, criaremos um usuário chamado L Lawliet usando a seguinte requisição:

```
Content-Type:application/json

{
    "name": "L lawliet",
    "email": "llawliet@email.com",
    "password": "lsecret",
    "permission": 1
}
```

Veja que especificamos o atributo `permission` com 1, que significa que o usuário criado terá
permissão `client`. Para saber mais sobre os parâmentros da criação de um usuário consulte
a referência de [registro de usuário](http://localhost/BusTrackerAPI/docs/index.html#api-Users-PostUsers).
Se tudo ocorrer bem devemos ter a seguinte resposta:

```
HTTP/1.1 201 CREATED

{
   "name": "L Lawliet",
   "email": "llawliet@email.com",
   "permission": 1,
   "token": "Me1wdd1TCDqKVym...QQ18pdEwjHuig=="
}

```

O usuário foi criado e como resposta recebemos também um token de acesso.
Esse token será usado para realizar as requisições de recursos com o usuário
L Lawliet.
Agora que temos um usuário com permissões de `client`, vamos requisitas a lista
de rotas disponíveis enviando o token de acesso no cabeçalho da requisição:

```
curl -i -H "Accept: application/json" -H "Token: Me1wdd1TCDqKVym...QQ18pdEwjHuig==" \
http://host/BusTrackerAPI/index.php/routes
```

Deve-se receber uma resposta como abaixo:
```
HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8

[
  {
    "id_routes": 86,
    "name": "Rota X",
    ...
  },
    ...
  {
    "id_routes": 890,
    "name": "UFC - MED",
     ...
  }
]
```

De forma análoga, para gerenciar recursos que necessitam de outras permisões como por
exemplo `tracker`, é necessário ter acesso a um token de um usuário com esta permissão.
Um usuário com permissão `admin` tem acesso a todos os recursos disponibilizados pela API.
