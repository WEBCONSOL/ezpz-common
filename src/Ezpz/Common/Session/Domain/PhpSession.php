<?php

declare(strict_types=1);

namespace Ezpz\Common\Session\Domain;

use Doctrine\ORM\Mapping as ORM;
use WC\Models\ListModel;

/**
 * The User class demonstrates how to annotate a simple
 * PHP class to act as a Doctrine entity.
 *
 * @ORM\Entity()
 * @ORM\Table(name="php_session")
 */
class PhpSession implements \JsonSerializable
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $session_id;
    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $user_id;
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $session_data;
    /**
     * @var int
     *
     * @ORM\Column(type="integer", length=10, nullable=true)
     */
    private $date_created;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", length=10, nullable=true)
     */
    private $last_updated;

    public function __construct(ListModel $session)
    {
        $this->session_id = $session->get('session_id');
        $this->user_id = $session->get('user_id', 0);
        $this->session_data = $session->get('session_data');
        if (is_array($this->session_data) || is_object($this->session_data)) {
            $this->session_data = json_encode($this->session_data);
        }
        $this->date_created = $session->get('date_created', strtotime('date_created'));
        $this->last_updated = $session->get('last_updated', strtotime('last_updated'));
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        return array(
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'session_data' => $this->session_data,
            'date_created' => $this->date_created,
            'last_updated' => $this->last_updated
        );
    }

    public function getId(): string {return $this->session_id;}
    public function getUserId(): int {return $this->user_id;}
    public function getData(): string {return $this->session_data;}
    public function getDateCreated(): int {return $this->date_created;}
    public function getLastUpdated(): int {return $this->last_updated;}
}