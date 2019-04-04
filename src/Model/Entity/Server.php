<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Server Entity
 *
 * @property int                       $id
 * @property string                    $name
 * @property string                    $url
 * @property \Cake\I18n\FrozenTime     $created
 * @property \Cake\I18n\FrozenTime     $modified
 *
 * @property \App\Model\Entity\Video[] $videos
 */
class Server extends Entity
{

	public const ID = 'id';
	public const NAME = 'name';
	public const URL = 'url';
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
		'name'     => true,
		'url'      => true,
		'created'  => true,
		'modified' => true,
		'videos'   => true
	];
}
