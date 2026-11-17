<?php
/**
 * =====================================================================
 *  Protected Component - Neon Creator Card
 *  پنل هوشمند فروش و مدیریت VPN - Creator Badge
 *  Status: Read-only | Encrypted View | Integrity Locked
 *  Hash: a109e5d5f7271b7bb4f79c502ae04754e6964c8438925d59b4a8ffc56653e30a
 *  WARNING: Do not edit, remove or obfuscate - Integrity check will fail
 *  Obfuscated via gzdeflate + base64 + runtime gzinflate
 * =====================================================================
 *  این فایل به صورت رمزگذاری‌شده محافظت شده و از داخل پنل غیرقابل ویرایش است
 */
namespace Core;
final class CreatorBadge {
    private const PAYLOAD = 'nVbLbttGFN3rK264CKhapOTEDYLKNNAERhugjyA1gnZljMhLaeDho8OhLDUw0Bj1Y5H/cGCgcZ04SNFFv2PY7volvcOXpTg22iwkzuM+zrlz7pCd9YBPwRcsyzwrxiR2fIlMJdLZlSy1NjoA11r4TAalxQ02PI5R1kYfMOM+/bWZaiPemIw4jLiTKSYza2O9z9swfYrTTiZ3l0IqrgSSeZayuNlQ8xQDC3hQ2WyVU4pobJYt/Vxmibzc60/utonSpTxZPmpSvQ94wlEEjj9BfwcidFYr8FD8WRwWv0BxVBzri+KgONRnUDzX52YKxTEUB/rs7xf6nH6n8PTxN3B7lAsxBDJ8o18VR8bgV31CLr/r039+fqEv9FlxtN5Pry1vlviciWyxuAwmEkPPmiiVZp/1+zym+o4li1w/ifozl0VcSvyJsVULqPBjVJ61PRIs3rFAoqDASZKiOdQPZHJGKoY2IgUwBfKsR+3KJZCr53zpt3jW5rTZDfiVG2E/wCkKgiW3WfaxsBUKXEK91SzcBLr1+j+Yx1xN8lFZ8Bb551R39rHYq4At8i+4+pKmN+GuPa5Fvdxi7+tKJP7OVd2bVSfkQjR63y+ek94rmcLtKGDZZAj6D6PxYl+f6JemG47NVJ/Q/0Uj+CfIAieJxRw2Y1/OU4UBPOW4ezOoVCYKfeXE9Li2KQ3GxZ40iYtD0C/1K8pPiI5Av6WGPKcWJJQH+vVfZ/o3wkpd2bbcUiPSpN4itzPTx2T9GvQ741Lst13fENanpgKGNDU2Pd7o0wVe7bAeNI/MlzxVG51+H74dhXnmM1MUutd4PAYW84gpnsTgQF0FDDp2mMe+WbW7zyjglEnYHsy2PItSnhDdU8O14kPjY1p9R9ANG4Km35q7qNinyUlxaA3rAKTGIPHzCGPlkk43BZrhg/mjwF64WbvGnIf2LRRdUrDKZdwE4N6gBwGFCelewh7sMq68gdklZjUdDENiALskURiLZJc2GyqkcH+n4lNmMO4bg+6zMo7jDOtskKHa4hEmubKNRw/WBt0h7DVutwhBHQSIk6twph4msSIunqmRS5d7piSBsQc9vrJaEip9V1baYWhzz6vMBcZjNalheGt3hiVFJXO8HtHq/cElJri6f+8+rMDXTE1cyeIgiezuJ2t3aiB7BDpDaBhQ6XYQU6qSEGC49MC8nQRCmpdVjhMDCBWSQMy7EJLYR5JNAJNEBFXITvWjWOaFq4CFCiWELMBOWaNMzQW6Scp8ruaeNSg1sYB6WW0LLorwZ9xseVbtDgP3XmYNl+3a0Kv1TnXWZrzXg09NtSp8RmgVM+JBDKYoa3WZL5JLgf6Yo5x/R7x9+hKxLffqx0sjVDOpYZuhy4Jgc0ohvuKZMhevbUVJnmFEqaxeSxRbCZnc0itdqSseJHkckHYeCk4xnlB6uxWQsZx56Prl3vd0HpLUE9KJzdvVH8pVlaSNUxm4KhIV/LE07ws1ty3HiWZWD2YrVjqzuv/Bek7W80Xrvaq8nb2ugUgfPfU18y8=';
    private const HASH = 'a109e5d5f7271b7bb4f79c502ae04754e6964c8438925d59b4a8ffc56653e30a';
    private const SIG = 'AS_VPN_PANEL_2024';
    public static function render(): void {
        $p = self::PAYLOAD;
        // anti-tamper: verify hash
        if (hash('sha256', $p) !== self::HASH) {
            echo '<div style="display:none">integrity</div>';
            return;
        }
        // decode: base64 + gzinflate (raw deflate)
        $html = @gzinflate(base64_decode($p));
        // fallback for gzcompress
        if ($html === false) {
            $html = @gzuncompress(base64_decode($p));
        }
        if ($html === false) {
            // last try: base64 only
            $html = @base64_decode($p);
        }
        if (!$html) return;
        // output safely
        echo $html;
    }
    public static function verify(): bool {
        return hash('sha256', self::PAYLOAD) === self::HASH;
    }
    // Prevent instantiation / cloning
    private function __construct(){}
    private function __clone(){}
    public function __wakeup(){ throw new \Exception('Locked'); }
}
