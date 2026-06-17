/**
 * SaltNotify Service Worker
 * Web Push bildirimlerini alır ve gösterir.
 * Domain root'tan erişilebilir: /sw-notify.js
 *
 * @version 1.1.0
 * @changelog
 *   1.1.0 - 2026-05-04
 *     - Add: actions (butonlar) desteği
 *     - Add: image, badge, renotify, silent desteği
 *     - Add: notificationclose event (analytics)
 *     - Add: action click routing
 *     - Fix: payload parse hatası graceful handling
 */

'use strict';

// ─── PUSH EVENT ──────────────────────────────────────────────────────────────

self.addEventListener('push', function (event) {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        payload = {
            title:   'Notification',
            body:    event.data.text(),
            url:     '/',
            actions: [],
        };
    }

    const title   = payload.title || 'Notification';
    const options = {
        body:             payload.body    || '',
        icon:             payload.icon    || '/favicon.ico',
        badge:            payload.badge   || '',
        image:            payload.image   || '',
        tag:              payload.tag     || 'sh-notify',
        data:             {
            url:     payload.url     || '/',
            actions: payload.actions || [],
        },
        actions:          payload.actions || [],
        vibrate:          [200, 100, 200],
        requireInteraction: false,
        renotify:         false,   // aynı tag varsa ses çıkarma
        silent:           false,
    };

    event.waitUntil(
        self.registration.showNotification( title, options )
    );
});

// ─── NOTIFICATION CLICK ──────────────────────────────────────────────────────

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const data    = event.notification.data || {};
    const actions = data.actions || [];

    // Action butonuna tıklandıysa o action'ın URL'sine git
    let url = data.url || '/';
    if (event.action) {
        const clicked = actions.find(a => a.action === event.action);
        if (clicked && clicked.url) {
            url = clicked.url;
        }
        // 'dismiss' action'ı sadece kapatır
        if (event.action === 'dismiss') return;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            // Zaten açık bir sekme varsa focus et
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Yoksa yeni sekme aç
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// ─── NOTIFICATION CLOSE (Analytics) ─────────────────────────────────────────

self.addEventListener('notificationclose', function (event) {
    const data = event.notification.data || {};
    // Kapatma event'ini backend'e bildir (opsiyonel analytics)
    // Sadece tag varsa gönder — gereksiz request'leri önle
    if (!event.notification.tag) return;

    // sendBeacon ile fire-and-forget
    if (navigator.sendBeacon) {
        navigator.sendBeacon('/wp-admin/admin-ajax.php', new URLSearchParams({
            action: 'sh_notify_push_closed',
            tag:    event.notification.tag,
        }));
    }
});

// ─── PUSH SUBSCRIPTION CHANGE ────────────────────────────────────────────────

self.addEventListener('pushsubscriptionchange', function (event) {
    // Subscription yenilendi — backend'e bildir
    event.waitUntil(
        self.registration.pushManager.subscribe(event.oldSubscription.options)
            .then(function (subscription) {
                return fetch('/wp-admin/admin-ajax.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    new URLSearchParams({
                        action:       'sh_notify_push_subscribe',
                        subscription: JSON.stringify(subscription),
                    }),
                });
            })
    );
});

// ─── HELPER: urlBase64ToUint8Array ───────────────────────────────────────────
// Frontend'de subscribe olurken applicationServerKey için gerekli

self.urlBase64ToUint8Array = function (base64String) {
    const padding  = '='.repeat((4 - base64String.length % 4) % 4);
    const base64   = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData  = atob(base64);
    const output   = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        output[i] = rawData.charCodeAt(i);
    }
    return output;
};
