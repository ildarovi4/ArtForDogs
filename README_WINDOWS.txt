ArtForDogs SMS Manager — сборка через Codemagic без Mac

1. Загрузить этот проект в GitHub.
2. Зарегистрироваться/войти в Codemagic.
3. Add application -> подключить GitHub -> выбрать репозиторий.
4. Codemagic увидит codemagic.yaml.
5. Сначала запустить workflow "ios-native" — это проверка, что проект собирается.
6. Для реального IPA нужен Apple Developer Program и подпись. В Codemagic добавить Apple Developer/App Store Connect credentials.
7. После настройки signing запустить workflow "ios-signed".
8. Готовый IPA появится в Artifacts.

Bundle ID:
com.artfordogs.smsmanager

API:
http://artfordogs.servehalflife.com:7777/api/ArtForDogs/server/api/

Важно:
- HTTP оставлен намеренно под текущий сервер.
- Для App Store лучше позднее перевести API на HTTPS.
- Текущий PWA/server не заменяется этим проектом.
