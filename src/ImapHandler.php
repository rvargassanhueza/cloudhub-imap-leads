<?php
namespace App;

class ImapHandler
{
    /**
     * @var string $host Dirección del servidor IMAP
     */
    private $host;

    /**
     * @var int $port Puerto del servidor IMAP
     */
    private $port;

    /**
     * @var string $username Nombre de usuario para la autenticación
     */
    private $username;

    /**
     * @var string $password Contraseña para la autenticación
     */
    private $password;

    /**
     * @var resource|false $imapStream Conexión activa al servidor IMAP
     */
    private $imapStream;

    /**
     * Constructor de la clase
     *
     * @param string $host Dirección del servidor IMAP
     * @param int $port Puerto del servidor IMAP
     * @param string $username Nombre de usuario para la autenticación
     * @param string $password Contraseña para la autenticación
     */
    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->port = 993;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Establece la conexión IMAP con el servidor
     *
     * @throws \Exception Si la conexión falla
     */
    public function connect()
    {
        $connectionString = "{" . $this->host . ":" . $this->port . "/imap/ssl/novalidate-cert}INBOX";

        try {
            $this->imapStream = imap_open(
                $connectionString,
                $this->username,
                $this->password
            );

            if (!$this->imapStream) {
                $errorMessage = imap_last_error();
                throw new \Exception("Falló la conexión IMAP: " . $errorMessage);
            }

            echo "IMAP conexión establecida" . PHP_EOL;
        } catch (\Exception $e) {
            echo "Error de conexión IMAP: " . $e->getMessage() . PHP_EOL;
            print_r(imap_errors());
            print_r(imap_alerts());
            die();
        }
    }

    /**
     * Obtiene los correos no leídos desde la bandeja de entrada
     *
     * @return array Lista de correos no leídos con detalles
     * @throws \Exception Si no hay conexión IMAP
     */
    public function getUnreadEmails()
    {
        if (!$this->imapStream) {
            throw new \Exception("No hay conexión IMAP establecida.");
        }

        $emails = imap_search($this->imapStream, 'UNSEEN');

        if (!$emails) {
            return [];
        }

        $emailsData = [];
        foreach ($emails as $emailId) {
            $overview = imap_fetch_overview($this->imapStream, $emailId, 0);
            $body = imap_fetchbody($this->imapStream, $emailId, 2);

            $emailsData[] = [
                'from' => $overview[0]->from ?? '',
                'subject' => $overview[0]->subject ?? '',
                'body' => $body,
            ];

            imap_delete($this->imapStream, $emailId);
        }

        imap_expunge($this->imapStream);

        return $emailsData;
    }

    /**
     * Cierra la conexión IMAP con el servidor
     */
    public function close()
    {
        if ($this->imapStream) {
            imap_close($this->imapStream);
        }
    }
}
