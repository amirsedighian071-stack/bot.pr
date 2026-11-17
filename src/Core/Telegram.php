<?php
namespace Core;

class Telegram {
    private string $token;
    private string $apiUrl;
    private int $timeout;

    public function __construct(?string $token = null) {
        $this->token = $token ?: Config::botToken();
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
        $this->timeout = (int)Config::get('telegram.timeout', 10);
    }

    public function request(string $method, array $params = []) {
        if (empty($this->token)) throw new \Exception("Bot token not set");
        $url = $this->apiUrl . $method;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) {
            Helper::log("Telegram $method error: $err", 'telegram.log');
            throw new \Exception("Telegram API error: $err");
        }
        $json = json_decode($res, true);
        if (!$json || !$json['ok']) {
            Helper::log("Telegram $method failed: $res", 'telegram.log');
            // در حالت خطا لاگ ولی exception نده برای وب‌هوک که گیر نکند
            return $json;
        }
        return $json;
    }

    public function sendMessage(int $chatId, string $text, array $replyMarkup = null, string $parseMode = 'HTML'): ?array {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup) $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        return $this->request('sendMessage', $params);
    }

    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $replyMarkup = null): ?array {
        $params = ['chat_id'=>$chatId, 'photo'=>$photo, 'caption'=>$caption, 'parse_mode'=>'HTML'];
        if ($replyMarkup) $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        return $this->request('sendPhoto', $params);
    }

    public function answerCallbackQuery(string $id, string $text = '', bool $alert = false): ?array {
        return $this->request('answerCallbackQuery', ['callback_query_id'=>$id, 'text'=>$text, 'show_alert'=>$alert]);
    }

    public function editMessageText(int $chatId, int $msgId, string $text, array $replyMarkup = null): ?array {
        $params = ['chat_id'=>$chatId, 'message_id'=>$msgId, 'text'=>$text, 'parse_mode'=>'HTML', 'disable_web_page_preview'=>true];
        if ($replyMarkup) $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        return $this->request('editMessageText', $params);
    }

    public function deleteMessage(int $chatId, int $msgId): ?array {
        return $this->request('deleteMessage', ['chat_id'=>$chatId, 'message_id'=>$msgId]);
    }

    public function getFile(string $fileId): ?array {
        return $this->request('getFile', ['file_id'=>$fileId]);
    }

    public function downloadFile(string $filePath, string $dest): bool {
        $url = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
        $data = @file_get_contents($url);
        if ($data === false) return false;
        return file_put_contents($dest, $data) !== false;
    }

    public function setWebhook(string $url, string $secret = ''): ?array {
        $params = ['url'=>$url, 'drop_pending_updates'=>false];
        if ($secret) $params['secret_token'] = $secret;
        return $this->request('setWebhook', $params);
    }

    public function deleteWebhook(bool $dropPending = false): ?array {
        return $this->request('deleteWebhook', ['drop_pending_updates'=>$dropPending]);
    }

    public function getWebhookInfo(): ?array {
        return $this->request('getWebhookInfo', []);
    }

    // هلسپر برای Inline Keyboard
    public static function inlineKeyboard(array $rows): array {
        return ['inline_keyboard' => $rows];
    }

    public static function btn(string $text, string $callbackData = '', string $url = ''): array {
        if ($url) return ['text'=>$text, 'url'=>$url];
        return ['text'=>$text, 'callback_data'=>$callbackData];
    }
}
