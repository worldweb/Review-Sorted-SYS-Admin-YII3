<?php

declare(strict_types=1);

namespace App\Domain\Chat;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class ChatRepository implements ChatRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?Chat
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $query = (new Query($this->db))
            ->select([
                'c.*',
                'service_label' => 'sc.label',
            ])
            ->from(['c' => '{{%chat}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = c.service_config_id')
            ->where(['c.id' => $id]);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return null;
            }
            $query->andWhere(['in', 'c.account_id', $accessible]);
        }

        /** @var array<string, mixed>|false $row */
        $row = $query->one();
        if ($row === null || $row === false) {
            return null;
        }

        $chat = Chat::fromDatabaseRow($row);
        $messages = $this->findMessagesForChat($id);

        return $chat->withMessages($messages);
    }

    public function findAllChats(?TenantContextInterface $context = null, int $limit = 30, int $offset = 0): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))
            ->select([
                'c.*',
                'service_label' => 'sc.label',
            ])
            ->from(['c' => '{{%chat}}'])
            ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = c.service_config_id')
            ->orderBy(['c.id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset);

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return [];
            }
            $query->andWhere(['in', 'c.account_id', $accessible]);
        }

        $rows = $query->all();
        $chats = [];
        foreach ($rows as $row) {
            $chats[] = Chat::fromDatabaseRow($row);
        }

        return $chats;
    }

    public function countChats(?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();
        $query = (new Query($this->db))->from('{{%chat}}');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $accessible = $ctx->getAccessibleAccountIds();
            if (empty($accessible)) {
                return 0;
            }
            $query->andWhere(['in', 'account_id', $accessible]);
        }

        return (int) $query->count();
    }

    public function markAsRead(int $chatId): bool
    {
        try {
            $this->db->createCommand()->update(
                '{{%chat}}',
                ['read' => Chat::READ_YES, 'updated_at' => time()],
                ['id' => $chatId]
            )->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function sendReply(int $chatId, int $userId, string $text, ?TenantContextInterface $context = null): bool
    {
        $chat = $this->findById($chatId, $context);
        if ($chat === null) {
            return false;
        }

        try {
            $now = time();
            $this->db->createCommand()->insert('{{%chat_message}}', [
                'account_id' => $chat->getAccountId(),
                'user_id' => $userId,
                'service_config_id' => $chat->getServiceConfigId(),
                'chat_id' => $chatId,
                'type' => ChatMessage::TYPE_OUT,
                'customer_id' => $chat->getCustomerId(),
                'customer_name' => $chat->getCustomerName(),
                'chat_text' => $text,
                'chat_date' => date('Y-m-d H:i:s'),
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, ChatMessage>
     */
    private function findMessagesForChat(int $chatId): array
    {
        $rows = (new Query($this->db))
            ->from('{{%chat_message}}')
            ->where(['chat_id' => $chatId])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = ChatMessage::fromDatabaseRow($row);
        }

        return $messages;
    }
}
