<?php
class NotificationTemplateManager
{
    private $conn;
    private $templatesTable = 'notification_templates';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureTemplateTable();
    }

    protected function ensureTemplateTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->templatesTable} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            variables JSON NOT NULL,
            type VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            INDEX (type)
        )";

        $this->conn->query($sql);
    }

    public function createTemplate($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->templatesTable}
            (name, subject, body, variables, type)
            VALUES (?, ?, ?, ?, ?)
        ");

        $variables = json_encode($data['variables']);
        $stmt->bind_param(
            'sssss',
            $data['name'],
            $data['subject'],
            $data['body'],
            $variables,
            $data['type']
        );

        return $stmt->execute();
    }

    public function renderTemplate($templateId, $data)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->templatesTable} WHERE id = ?
        ");

        $stmt->bind_param('i', $templateId);
        $stmt->execute();
        $template = $stmt->get_result()->fetch_assoc();

        if (!$template) {
            throw new Exception('Template not found');
        }

        $html = $template['body'];
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return [
            'subject' => $this->replaceVariables($template['subject'], $data),
            'body' => $html
        ];
    }

    protected function replaceVariables($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }
}
