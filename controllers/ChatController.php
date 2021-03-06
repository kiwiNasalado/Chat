<?php

namespace app\controllers;

use app\components\Chat;
use app\models\activeRecord\Client;
use app\models\ClientRoomDetails as CRDModel;
use app\models\activeRecord\Room;
use app\models\Clients;
use app\models\LoginForm;
use app\models\Messages;
use app\models\Rooms;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ChatController extends Controller
{

    public function actionIndex()
    {
        $identifier = Yii::$app->getRequest()->get('identifier');

        if (Chat::getIsPortFree() || empty($identifier)) {
            return $this->redirect('/chat/login');
        }
        $client = Client::findOne(['identifier' => $identifier]);
        if (null === $client) {
            return $this->redirect('/chat/login');
        }

        $rooms = Rooms::getInstance()->getAllowedRooms($identifier);
        return $this->render(
            'index',
            [
                'chatPort'   => Chat::CHAT_PORT,
                'identifier' => $identifier,
                'rooms'      => $rooms
            ]);
    }

    public function actionRoom()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = Yii::$app->getRequest()->get('id', 0);
        $identifier = Yii::$app->getRequest()->get('identifier', '');
        $room = Room::findOne(['id' => $id]);
        $client = Client::findOne(['identifier' => $identifier]);
        if (null === $client) {
            throw new ForbiddenHttpException();
        }
        if (null !== $room) {
            $room = $room->toArray();
        }
        if (
            Chat::getIsPortFree() ||
            empty($id) ||
            empty($identifier) ||
            (
                0 === (int) $room['isPublic'] &&
                !Rooms::getInstance()->checkIsRoomAllowed($identifier, (int) $id)
            )
        ) {
            return '';
        }
        $messages = Messages::getInstance()->getMessages((int) $id, (int) $client->id);


        CRDModel::getInstance()->setLastVisitDatetime((int) $client->id, [(int) $id]);

        return json_encode(['messages' => $messages, 'room' => $room]);
    }

    public function actionUpdateRoom()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id         = Yii::$app->getRequest()->get('id', 0);
        $identifier = Yii::$app->getRequest()->get('identifier', '');
        $page       = Yii::$app->getRequest()->get('page', 0);

        $room = Room::findOne(['id' => $id]);
        if (null !== $room) {
            $room = $room->toArray();
        }
        if (
            Chat::getIsPortFree() ||
            $page <= 0 ||
            empty($id) ||
            empty($identifier) ||
            (
                0 === (int) $room['isPublic'] &&
                !Rooms::getInstance()->checkIsRoomAllowed($identifier, (int) $id)
            )
        ) {
            return '';
        }


        $messages = Messages::getInstance()->getMessages((int) $id, null, $page);

        return json_encode(['messages' => $messages, 'room' => $room]);
    }

    public function actionUpdateRoomList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $identifier = Yii::$app->getRequest()->get('identifier', '');
        $page       = Yii::$app->getRequest()->get('page', 0);
        if (Chat::getIsPortFree()) {
            return '';
        }

        $rooms = Rooms::getInstance()->getAllowedRooms($identifier, $page);

        return json_encode(['rooms' => $rooms]);

    }

    public function actionLogin()
    {
        $this->layout = '//login_layout';
        $form = new LoginForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            $client = Client::findOne(['email' => $form->email]);
            if (null === $client) {
                $email = $form->email;
                $identifier = Clients::getInstance()->generateIdentifier($email);
                $client = new Client();
                $client->email = $email;
                $client->identifier = $identifier;
                $client->save();

                $rooms = array_column(Rooms::getInstance()->getAllowedRooms($identifier), 'id');

                CRDModel::getInstance()->setLastVisitDatetime($client->id, $rooms);
            }
            $this->redirect('/?identifier=' . $client->identifier);
        }

        return $this->render('login', ['form' => $form, 'isChatOnline' => !Chat::getIsPortFree()]);
    }
}
