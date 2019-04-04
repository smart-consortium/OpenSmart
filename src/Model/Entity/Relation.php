<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Relation Entity
 *
 * @property int                     $id
 * @property int                     $video_id
 * @property int                     $child_id
 * @property \Cake\I18n\FrozenTime   $created
 * @property \Cake\I18n\FrozenTime   $modified
 *
 * @property \App\Model\Entity\Video $video
 */
class Relation extends Entity
{

	public const VIDEO_ID = 'video_id';
	public const CHILD_ID = 'child_id';
	public const CREATED = 'created';
	public const MODIFIED = 'modified';

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
		'video_id' => true,
		'child_id' => true,
		'created'  => true,
		'modified' => true,
		'video'    => true
	];
}
