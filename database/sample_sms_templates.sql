-- Add more SMS templates for different scenarios
INSERT INTO sms_templates (name, code, content, variables, is_active) VALUES
(
    'Rezervasyon Hatırlatma',
    'BOOKING_REMINDER',
    'Sayın {customer},\nYarınki {time} rezervasyonunuzu hatırlatmak isteriz.\nCarWash #{carwash_name}\nİptal/Değişiklik için: {booking_url}',
    '["customer", "time", "carwash_name", "booking_url"]',
    1
),
(
    'İptal Bildirimi',
    'BOOKING_CANCELLED',
    'Sayın {customer},\n#{booking_id} nolu rezervasyonunuz iptal edilmiştir.\nİade tutarı: {refund_amount} TL\nYeni rezervasyon: {booking_url}',
    '["customer", "booking_id", "refund_amount", "booking_url"]',
    1
),
(
    'Yıkama Tamamlandı',
    'SERVICE_COMPLETED',
    'Sayın {customer},\nAracınızın yıkaması tamamlanmıştır. Aracınızı teslim alabilirsiniz.\nMemnuniyet değerlendirmesi için: {feedback_url}',
    '["customer", "feedback_url"]',
    1
),
(
    'Özel İndirim',
    'SPECIAL_OFFER',
    'Özel Teklif!\n{discount_amount}TL indirim kuponu kazandınız.\nKod: {coupon_code}\nSon kullanım: {expiry_date}\nDetaylar: {offer_url}',
    '["discount_amount", "coupon_code", "expiry_date", "offer_url"]',
    1
),
(
    'Sadakat Programı',
    'LOYALTY_PROGRAM',
    'Tebrikler {customer}!\n{points} sadakat puanı kazandınız.\nToplam puanınız: {total_points}\nPuan kullanımı: {rewards_url}',
    '["customer", "points", "total_points", "rewards_url"]',
    1
),
(
    'Bakım Hatırlatması',
    'MAINTENANCE_REMINDER',
    'Sayın {customer},\nSon yıkamanızın üzerinden {days} gün geçti. Aracınızın bakım zamanı geldi.\nRezv. için: {booking_url}',
    '["customer", "days", "booking_url"]',
    1
),
(
    'Yağmur Teklifi',
    'RAINY_DAY_OFFER',
    'Yağmurlu Gün Fırsatı!\nBugün tüm yıkamalarda %{discount_percent} indirim.\nKod: {promo_code}\nRezv: {booking_url}',
    '["discount_percent", "promo_code", "booking_url"]',
    1
),
(
    'Doğum Günü',
    'BIRTHDAY_SPECIAL',
    'İyi ki doğdun {customer}!\nDoğum gününe özel %{discount_percent} indirim hediyemiz.\nKod: {birthday_code}\nGeçerlilik: {valid_date}',
    '["customer", "discount_percent", "birthday_code", "valid_date"]',
    1
);