<?php

namespace Ora\User;

use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Rhumsaa\Uuid\Uuid;
use Ora\ReadModel\OrganizationMembership;
use Ora\ReadModel\Organization;

/**
 * @ORM\Entity @ORM\Table(name="users")
 *
 */
class User
{	   
	const STATUS_ACTIVE = 1;
	 
	/**
	 * @ORM\Id @ORM\Column(type="string") 
	 * @var string
	 */
	protected $id;
	
	/**
	 * @ORM\Column(type="datetime")
	 * @var DateTime
	 */
	protected $createdAt;
	
	/**
	 * @ORM\ManyToOne(targetEntity="Ora\User\User")
     * @ORM\JoinColumn(name="createdBy_id", referencedColumnName="id", nullable=TRUE)
	 */
	protected $createdBy;
	
    /**
     * @ORM\Column(type="datetime")
     * @var datetime
     */
    protected $mostRecentEditAt;
    
    /**
     * @ORM\ManyToOne(targetEntity="Ora\User\User")
     * @ORM\JoinColumn(name="mostRecentEditBy_id", referencedColumnName="id", nullable=TRUE)
     */
    protected $mostRecentEditBy;
    
	/**
	 * @ORM\Column(type="string", length=100, nullable=TRUE)
	 * @var string
	 */
	private $firstname;

	/**
	 * @ORM\Column(type="string", length=100, nullable=TRUE)
	 * @var string
	 */
	private $lastname;

	/**
	 * @ORM\Column(type="string", length=200, unique=TRUE)
	 * @var string
	 */
	private $email;
		
	/**
	 * @ORM\Column(type="integer")
	 * @var int
	 */
	private $status;
	
	/**
	 * @ORM\Column(type="string")
	 * @var string
	 */
	private $picture;
	
	/**
	 * @ORM\OneToMany(targetEntity="Ora\ReadModel\OrganizationMembership", mappedBy="member")
	 * @var ArrayCollection
	 */
	protected $memberships;
	
	private function __construct() {
		$this->memberships = new ArrayCollection();
	}
	
	public static function create(User $createdBy = null) {
		$rv = new self();
		$rv->id = Uuid::uuid4()->toString();
		$rv->status = self::STATUS_ACTIVE;
		$rv->createdAt = new \DateTime();
		$rv->createdBy = $createdBy;
		$rv->mostRecentEditAt = $rv->createdAt;
		$rv->mostRecentEditBy = $rv->createdBy;
		return $rv;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getCreatedAt() {
		return $this->createdAt;
	}
	
	public function setCreatedAt(\DateTime $when) {
		$this->createdAt = $when;
		return $this->createdAt;
	}
	
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    
    public function setCreatedBy(User $user) {
    	$this->createdBy = $user;
    	return $this->createdBy;
    }

    public function getMostRecentEditAt() {
        return $this->mostRecentEditAt;
    }
    
	public function setMostRecentEditAt(\DateTime $when) {
		$this->mostRecentEditAt = $when;
		return $this->mostRecentEditAt;
	}
	
    public function getMostRecentEditBy() {
        return $this->mostRecentEditBy;
    }
    
    public function setMostRecentEditBy(User $user) {
    	$this->mostRecentEditBy = $user;
    	return $this->mostRecentEditBy;
    }

    public function equals(User $object = null) {
		if(is_null($object)) {
			return false;
		}
		return $this->id == $object->getId();
	}
	
	public function setFirstname($firstname)
	{
		$this->firstname = $firstname;
		return $this;
	}
	
	public function getFirstname()
	{
		return $this->firstname;
	}

	public function setLastname($lastname)
	{
		$this->lastname = $lastname;
		return $this;
	}
	
	public function getLastname()
	{
		return $this->lastname;
	}

	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}
	
	public function getEmail()
	{
		return $this->email;
	}

	public function setStatus($status)
	{
		$this->status = $status;
		return $this;
	}
	
	public function getStatus()
	{
		return $this->status;
	}	
	
	public function getOrganizationMemberships()
	{
		return $this->memberships;
	}
	
	public function setPicture($url) {
		$this->picture = $url;
		return $this;
	}
	
	public function getPicture() {
		return $this->picture;
	}
	
	public function isMemberOf(Organization $organization) {
		$rv = $this->memberships->exists(function($key, $value) use ($organization) {
			return $value->getOrganization()->equals($organization);
		});
		return $rv;
	}
}