<?php

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int                       $id
 * @property string                    $username
 * @property string                    $display_name
 * @property string                    $first_name
 * @property string                    $family_name
 * @property string                    $email
 * @property string                    $password
 * @property \Cake\I18n\FrozenTime     $created
 * @property \Cake\I18n\FrozenTime     $modified
 *
 * @property \App\Model\Entity\Video[] $videos
 */
class User extends Entity
{
	public const ID = 'id';
	public const USERNAME = 'username';
	public const DISPLAY_NAME = 'display_name';
	public const FIRST_NAME = 'first_name';
	public const FAMILY_NAME = 'family_name';
	public const EMAIL = 'email';
	public const PASSWORD = 'password';
	public const CREATED = 'created';
	public const MODIFIED = 'modified';
	public const VIDEOS = 'videos';

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'username'     => true,
		'display_name' => true,
		'first_name'   => true,
		'family_name'  => true,
		'email'        => true,
		'password'     => true,
		'created'      => true,
		'modified'     => true,
		'videos'       => true
	];

	/**
	 * Fields that are excluded from JSON versions of the entity.
	 *
	 * @var array
	 */
	protected $_hidden = [
		'password'
	];

	/**
	 * save password hash
	 * @param string $password Password strings
	 * @return bool|string Password hash or false on failure
	 */
	protected function _setPassword($password)
	{
		if (strlen($password) > 0) {
			return (new DefaultPasswordHasher())->hash($password);
		}
	}
}
