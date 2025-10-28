<?php
// SMS Template Manager Class
class SMSTemplateManager
{
    private $conn;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    public function getTemplate($code)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM sms_templates 
            WHERE code = ? AND is_active = 1
        ");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function renderTemplate($code, $data)
    {
        $template = $this->getTemplate($code);
        if (!$template) {
            throw new Exception("Template not found: $code");
        }

        $content = $template['content'];
        $variables = json_decode($template['variables'], true);

        // Validate required variables
        foreach ($variables as $var) {
            if (!isset($data[$var])) {
                throw new Exception("Missing required variable: $var");
            }
            $content = str_replace("{{$var}}", $data[$var], $content);
        }

        return $content;
    }

    public function getAllTemplates()
    {
        $result = $this->conn->query("SELECT * FROM sms_templates ORDER BY name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateTemplate($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE sms_templates 
            SET name = ?, content = ?, variables = ?, is_active = ?
            WHERE id = ?
        ");

        $variables = json_encode($data['variables']);
        $stmt->bind_param(
            'sssis',
            $data['name'],
            $data['content'],
            $variables,
            $data['is_active'],
            $id
        );

        return $stmt->execute();
    }
}
