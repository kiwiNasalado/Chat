<?php

use yii\db\Migration;

/**
 * Class m220212_092933_init_tables
 */
class m220212_092933_init_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            'room',
            [
                'id'                   => $this->primaryKey(),
                'title'                => $this->string(64)->notNull(),
                'isPublic'             => $this->tinyInteger()->notNull(),
                'identifier'           => $this->string(32)->unique()->notNull(),
                'historyDaysLimit'     => $this->tinyInteger()->defaultValue(30),
                'historyMessagesLimit' => $this->smallInteger()->defaultValue(5000),
            ]
        );

        $this->createTable(
            'client',
            [
                'id'                   => $this->primaryKey(),
                'email'                => $this->string(32)->unique()->notNull(),
                'identifier'           => $this->string(32)->unique()->notNull(),
            ],
        );

        $this->createTable(
            'message',
            [
                'id'      => $this->primaryKey(),
                'sendAt'  => $this->dateTime()->notNull(),
                'message' => $this->string(255)->notNull(),
                'roomId'  => $this->integer()->notNull(),
                'ownerId' => $this->integer()->notNull(),
                'isCommand' => $this->tinyInteger()->defaultValue(0)
            ],
        );

        $this->createIndex(
            'message_sendAt_roomId',
            'message',
            [
                'sendAt',
                'roomId'
            ]
        );


        $this->createIndex(
            'message_roomId',
            'message',
            'roomId'
        );

        $this->createTable(
            'room_access',
            [
                'id'      => $this->primaryKey(),
                'emailId' => $this->integer()->notNull(),
                'roomId'  => $this->integer()->notNull()
            ]
        );

        $this->createIndex(
            'room_access_emailId',
            'room_access',
            'emailId'
        );

        $this->createTable(
            'client_room_details',
            [
                'emailId' => $this->integer()->notNull(),
                'roomId'  => $this->integer()->notNull(),
                'lastVisitDatetime' => $this->dateTime()->defaultValue(null),
            ]
        );

        $this->createIndex(
            'client_room_details_emailId_roomId',
            'client_room_details',
            ['emailId', 'roomId'],
            true
        );

        $this->insert(
            'room',
            [
                'title'    => 'FLOOD',
                'isPublic' => 1,
                'identifier' => '123'
            ]
        );

        $this->insert(
            'room',
            [
                'title'    => 'FLOOD V2.0',
                'isPublic' => 1,
                'identifier' => '456'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220212_092933_init_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220212_092933_init_tables cannot be reverted.\n";

        return false;
    }
    */
}
