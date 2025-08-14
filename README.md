# Discord RE

В каком то смысле это мессенджер с открытым исходным кодом, вы можете его развернуть где угодно, хоть у себя на компьютере, и общаться со своими друзьями, даже если в вашей стране блокируют интернет.

Разработан на PHP с поддержкой real-time сообщений через WebSocket.

## Основные возможности

### ✅ Сейчас реализовано/частично реализовано:
- **Аутентификация и регистрация пользователей**
  - Безопасная регистрация с проверкой паролей
  - JWT-токены для аутентификации
  - Защищенные API endpoints

- **Система каналов**
  - Создание публичных и приватных каналов
  - Присоединение к каналам и выход из них
  - Управление участниками каналов

- **Личные сообщения**
  - Прямые сообщения между пользователями
  - История переписки

- **Real-time коммуникация**
  - WebSocket сервер для мгновенной доставки сообщений
  - Уведомления о новых сообщениях
  - Статус пользователей (онлайн/оффлайн)

- **Современный интерфейс**
  - Адаптивный дизайн
  - Интуитивно понятный UI
  - Поддержка мобильных устройств

## Архитектура

### Backend (PHP)
- **Framework**: Slim Framework 4
- **Database**: SQLite с Doctrine ORM
- **Authentication**: JWT токены
- **WebSocket**: Ratchet/ReactPHP
- **API**: RESTful endpoints

### Frontend
- **Technology**: Vanilla JavaScript, HTML5, CSS3
- **WebSocket Client**: Native WebSocket API
- **UI**: Responsive design с современным интерфейсом

### База данных
- **Users**: Пользователи системы
- **Channels**: Каналы для группового общения
- **Messages**: Сообщения (канальные и личные)
- **ChannelMembers**: Связь пользователей с каналами

## Установка и запуск

### Требования
- PHP 8.1+
- Composer
- SQLite
- Python 3 (для HTTP сервера фронтенда)

### Установка зависимостей
```bash
cd messenger-app
composer install
```

### Создание базы данных
```bash
php bin/create-database.php
```

### Запуск серверов

1. **API сервер** (порт 8080):
```bash
php -S 0.0.0.0:8080 -t public
```

2. **WebSocket сервер** (порт 8081):
```bash
php bin/websocket-server.php
```

3. **Frontend сервер** (порт 3000):
```bash
cd frontend
python3 -m http.server 3000
```

## API Endpoints

### Аутентификация
- `POST /api/register` - Регистрация пользователя
- `POST /api/login` - Вход в систему

### Каналы
- `GET /api/channels` - Список доступных каналов
- `POST /api/channels` - Создание канала
- `POST /api/channels/{id}/join` - Присоединение к каналу
- `POST /api/channels/{id}/leave` - Выход из канала
- `GET /api/channels/{id}/messages` - Сообщения канала

### Пользователи
- `GET /api/user/{id}` - Информация о пользователе
- `GET /api/user/{id}/messages` - Личные сообщения
- `GET /api/user/channels` - Каналы пользователя

### Служебные
- `GET /api/health` - Проверка состояния API

## WebSocket Protocol

### Аутентификация
```json
{
  "type": "auth",
  "token": "jwt-token-here"
}
```

### Отправка сообщения
```json
{
  "type": "message",
  "content": "Текст сообщения",
  "channel_id": 1,
  "receiver_id": null
}
```

### Получение сообщения
```json
{
  "type": "new_message",
  "message_id": 1,
  "sender_id": 1,
  "sender_username": "username",
  "channel_id": 1,
  "receiver_id": null,
  "content": "Текст сообщения",
  "sent_at": "2025-08-14 16:30:00"
}
```

## Структура проекта

```
project/
├── bin/                    # Исполняемые скрипты
│   ├── create-database.php # Создание БД
│   └── websocket-server.php # WebSocket сервер
├── config/                 # Конфигурация
│   └── doctrine.php       # Настройки Doctrine ORM
├── frontend/              # Клиентская часть
│   ├── index.html        # Главная страница
│   ├── styles.css        # Стили
│   └── app.js           # JavaScript логика
├── public/               # Публичная директория API
│   └── index.php        # Точка входа API
├── src/                 # Исходный код PHP
│   ├── Controller/      # Контроллеры
│   ├── Entity/         # Сущности БД
│   ├── Service/        # Бизнес-логика
│   └── WebSocket/      # WebSocket обработчики
├── .env                # Переменные окружения
├── composer.json       # Зависимости PHP
└── README.md          # Документация
```

## Лицензия

MIT License - свободное использование и модификация.

