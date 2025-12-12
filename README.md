# Laravel Named Route Binding

[![Latest Version on Packagist](https://img.shields.io/packagist/v/umutcangungormus/laravel-named-route-binding.svg?style=flat-square)](https://packagist.org/packages/umutcangungormus/laravel-named-route-binding)
[![Total Downloads](https://img.shields.io/packagist/dt/umutcangungormus/laravel-named-route-binding.svg?style=flat-square)](https://packagist.org/packages/umutcangungormus/laravel-named-route-binding)

Laravel'de route parametrelerini controller metodlarına **isimlerine göre** bağlayan bir paket. Artık parametre sırası önemli değil!

## Problem

Laravel'de varsayılan olarak route parametreleri controller metodlarına **sırasına göre** geçirilir:

```php
// routes/web.php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// PostController.php - Parametreler SIRAYLA gelmeli!
public function show($user, $post)
{
    // $user = {user} route parametresi
    // $post = {post} route parametresi
}

// ❌ Bu ÇALIŞMAZ! Sıra yanlış olduğu için $post'a user değeri gelir
public function show($post, $user)
{
    // $post = {user} route parametresi (YANLIŞ!)
    // $user = {post} route parametresi (YANLIŞ!)
}
```

## Çözüm

Bu paket ile parametreler **isimlerine göre** eşleştirilir:

```php
// routes/web.php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// PostController.php - Artık sıra önemli DEĞİL!
public function show($post, $user)
{
    // ✅ $post = {post} route parametresi (İSİMLE EŞLEŞTİ!)
    // ✅ $user = {user} route parametresi (İSİMLE EŞLEŞTİ!)
}

// İstediğiniz sırada yazabilirsiniz
public function show($user, $post) // ✅ Çalışır
public function show($post, $user) // ✅ Çalışır
```

## Kurulum

Composer ile paketi yükleyin:

```bash
composer require umutcangungormus/laravel-named-route-binding
```

Paket otomatik olarak keşfedilir (Laravel 5.5+). Manuel kayıt gerekiyorsa:

```php
// config/app.php
'providers' => [
    // ...
    UmutcanGungormus\NamedRouteBinding\NamedRouteBindingServiceProvider::class,
],
```

## Yapılandırma (Opsiyonel)

Yapılandırma dosyasını yayınlayın:

```bash
php artisan vendor:publish --provider="UmutcanGungormus\NamedRouteBinding\NamedRouteBindingServiceProvider"
```

`config/named-route-binding.php`:

```php
return [
    // Özelliği etkinleştir/devre dışı bırak
    'enabled' => env('NAMED_ROUTE_BINDING_ENABLED', true),
];
```

## Özellikler

### 1. İsimle Eşleştirme

```php
Route::get('/categories/{category}/products/{product}', [ProductController::class, 'show']);

// Her iki yazım da çalışır
public function show($category, $product) { }
public function show($product, $category) { }
```

### 2. Snake_case / CamelCase Desteği

Route parametresi `user_id` ise, metod parametresi `userId` veya `user_id` olabilir:

```php
Route::get('/users/{user_id}', [UserController::class, 'show']);

// Her ikisi de çalışır
public function show($user_id) { }
public function show($userId) { }
```

### 3. Dependency Injection Desteği

Request ve diğer bağımlılıklar otomatik enjekte edilir:

```php
Route::get('/users/{user}', [UserController::class, 'show']);

public function show(Request $request, $user)
{
    // $request otomatik enjekte edilir
    // $user route parametresinden gelir
}

// Sıra değiştirilebilir
public function show($user, Request $request) { }
```

### 4. Varsayılan Değerler

```php
Route::get('/posts/{post}', [PostController::class, 'show']);

public function show($post, $format = 'json')
{
    // $format varsayılan değeri kullanır
}
```

### 5. Nullable Parametreler

```php
public function show($post, ?string $optional)
{
    // $optional null olur eğer route'da yoksa
}
```

## Route Model Binding ile Kullanım

Laravel'in Route Model Binding özelliği ile tam uyumludur:

```php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// Type-hinted modeller otomatik çözümlenir
public function show(Post $post, User $user)
{
    // $post ve $user model instance'ları olarak gelir
}
```

## Gerçek Dünya Örnekleri

### Örnek 1: E-ticaret

```php
// routes/web.php
Route::get('/shops/{shop}/categories/{category}/products/{product}', [ProductController::class, 'show']);

// ProductController.php - İstediğiniz sırada
public function show(
    Request $request,
    Product $product,    // 3. route parametresi
    Category $category,  // 2. route parametresi  
    Shop $shop          // 1. route parametresi
) {
    // Hepsi doğru şekilde eşleşir!
}
```

### Örnek 2: API Resource

```php
// routes/api.php
Route::get('/teams/{team}/members/{member}/tasks/{task}', [TaskController::class, 'show']);

// TaskController.php
public function show(Task $task, Member $member, Team $team)
{
    $this->authorize('view', [$task, $team]);
    
    return new TaskResource($task);
}
```

## Test

```bash
composer test
```

## Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır!

## Lisans

MIT Lisansı. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

---

# 🇬🇧 English Version

A Laravel package that binds route parameters to controller method arguments **by name** instead of by order. Parameter order no longer matters!

## The Problem

By default in Laravel, route parameters are passed to controller methods **in order**:

```php
// routes/web.php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// PostController.php - Parameters must be IN ORDER!
public function show($user, $post)
{
    // $user = {user} route parameter
    // $post = {post} route parameter
}

// ❌ This DOESN'T WORK! Wrong order means $post gets user value
public function show($post, $user)
{
    // $post = {user} route parameter (WRONG!)
    // $user = {post} route parameter (WRONG!)
}
```

## The Solution

With this package, parameters are matched **by name**:

```php
// routes/web.php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// PostController.php - Order NO LONGER matters!
public function show($post, $user)
{
    // ✅ $post = {post} route parameter (MATCHED BY NAME!)
    // ✅ $user = {user} route parameter (MATCHED BY NAME!)
}

// Write them in any order you want
public function show($user, $post) // ✅ Works
public function show($post, $user) // ✅ Works
```

## Installation

Install the package via Composer:

```bash
composer require umutcangungormus/laravel-named-route-binding
```

The package will be auto-discovered (Laravel 5.5+). For manual registration:

```php
// config/app.php
'providers' => [
    // ...
    UmutcanGungormus\NamedRouteBinding\NamedRouteBindingServiceProvider::class,
],
```

## Configuration (Optional)

Publish the configuration file:

```bash
php artisan vendor:publish --provider="UmutcanGungormus\NamedRouteBinding\NamedRouteBindingServiceProvider"
```

`config/named-route-binding.php`:

```php
return [
    // Enable/disable the feature
    'enabled' => env('NAMED_ROUTE_BINDING_ENABLED', true),
];
```

## Features

### 1. Name-Based Matching

```php
Route::get('/categories/{category}/products/{product}', [ProductController::class, 'show']);

// Both work
public function show($category, $product) { }
public function show($product, $category) { }
```

### 2. Snake_case / CamelCase Support

If route parameter is `user_id`, method parameter can be `userId` or `user_id`:

```php
Route::get('/users/{user_id}', [UserController::class, 'show']);

// Both work
public function show($user_id) { }
public function show($userId) { }
```

### 3. Dependency Injection Support

Request and other dependencies are automatically injected:

```php
Route::get('/users/{user}', [UserController::class, 'show']);

public function show(Request $request, $user)
{
    // $request is auto-injected
    // $user comes from route parameter
}

// Order can be changed
public function show($user, Request $request) { }
```

### 4. Default Values

```php
Route::get('/posts/{post}', [PostController::class, 'show']);

public function show($post, $format = 'json')
{
    // $format uses default value
}
```

### 5. Nullable Parameters

```php
public function show($post, ?string $optional)
{
    // $optional is null if not in route
}
```

## Usage with Route Model Binding

Fully compatible with Laravel's Route Model Binding:

```php
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show']);

// Type-hinted models are automatically resolved
public function show(Post $post, User $user)
{
    // $post and $user come as model instances
}
```

## Real World Examples

### Example 1: E-commerce

```php
// routes/web.php
Route::get('/shops/{shop}/categories/{category}/products/{product}', [ProductController::class, 'show']);

// ProductController.php - Any order you want
public function show(
    Request $request,
    Product $product,    // 3rd route parameter
    Category $category,  // 2nd route parameter  
    Shop $shop          // 1st route parameter
) {
    // All matched correctly!
}
```

### Example 2: API Resource

```php
// routes/api.php
Route::get('/teams/{team}/members/{member}/tasks/{task}', [TaskController::class, 'show']);

// TaskController.php
public function show(Task $task, Member $member, Team $team)
{
    $this->authorize('view', [$task, $team]);
    
    return new TaskResource($task);
}
```

## Testing

```bash
composer test
```

## Contributing

Pull requests are welcome!

## License

MIT License. See [LICENSE](LICENSE) for details.

