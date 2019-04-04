<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Video Entity
 *
 * @property int                           $id
 * @property int                           $user_id
 * @property string                        $svid
 * @property int                           $mode
 * @property int                           $server_id
 * @property int                           $status
 * @property int                           $reference
 * @property int                           $duration
 * @property string                        $caption
 * @property string                        $path
 * @property \Cake\I18n\FrozenTime         $created
 * @property \Cake\I18n\FrozenTime         $modified
 *
 * @property \App\Model\Entity\User        $user
 * @property \App\Model\Entity\Server      $server
 * @property \App\Model\Entity\Parameter[] $parameters
 * @property \App\Model\Entity\Relation[]  $relations
 */
class Video extends Entity
{

	public const USER_ID = 'user_id';
	public const SVID = 'svid';
	public const MODE = 'mode';
	public const SERVER_ID = 'server_id';
	public const PATH = 'path';
	public const CAPTION = 'caption';
	public const REFERENCE = 'reference';
	public const DURATION = 'duration';
	public const STATUS = 'status';
	public const CREATED = 'created';
	public const MODIFIED = 'modified';
	public const USER = 'user';
	public const SERVER = 'server';
	public const PARAMETERS = 'parameters';

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
		'user_id'    => true,
		'svid'       => true,
		'mode'       => true,
		'server_id'  => true,
		'path'       => true,
		'caption'    => true,
		'reference'  => true,
		'duration'   => true,
		'status'     => true,
		'created'    => true,
		'modified'   => true,
		'user'       => true,
		'server'     => true,
		'parameters' => true,
		'relations'  => true
	];
}
