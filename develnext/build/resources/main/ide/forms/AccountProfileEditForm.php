<?php
namespace ide\forms;

use facade\Async;
use ide\account\api\ServiceResponse;
use ide\Ide;
use ide\ui\Notifications;
use php\gui\framework\AbstractForm;
use php\gui\layout\UXAnchorPane;
use php\gui\UXFileChooser;
use php\gui\UXImage;
use php\gui\UXImageArea;
use php\io\Stream;
use php\lib\str;

/**
 * Class AccountProfileEditForm
 * @package ide\forms
 *
 *
 * @property UXImageArea $avatarArea
 */
class AccountProfileEditForm extends AbstractForm
{
    /**
     * @var bool
     */
    protected $avatarChanged = false;

    /**
     * @var null|string
     */
    protected $avatarFile = null;

    /**
     * @var UXFileChooser
     */
    protected $dialog;

    protected function init()
    {
        parent::init();

        $dialog = new UXFileChooser();
        $dialog->extensionFilters = [
            ['description' => 'Изображения (jpg, png, gif)', 'extensions' => ['*.jpg', '*.jpeg', '*.png', '*.gif']]
        ];

        $this->dialog = $dialog;

        $this->icon->image = ico('flatAccount48')->image;

        $avatarArea = new UXImageArea();
        $avatarArea->centered = true;
        $avatarArea->stretch = true;
        $avatarArea->smartStretch = true;
        $avatarArea->proportional = true;

        UXAnchorPane::setAnchor($avatarArea, 0);

        $this->avatarPane->add($avatarArea);
        $avatarArea->toBack();

        $this->avatarArea = $avatarArea;
    }

    public function update()
    {
        $this->showPreloader();

        Ide::service()->account()->getAsync(function (ServiceResponse $response) {
            if ($response->isSuccess()) {
                $data = $response->result();

                Ide::service()->file()->loadImage($data['avatarId'], $this->avatarArea, 'noAvatar.jpg');

                $this->nameField->text = $data['login'];
                $this->emailLabel->text = $data['email'];

                $this->hidePreloader();
            } else {
                Notifications::showAccountUnavailable();
                $this->hide();
            }
        });
    }

    /**
     * @event show
     */
    public function doShow()
    {
        $this->update();
    }

    /**
     * @event saveButton.action
     */
    public function doSave()
    {
        $this->showPreloader('Сохранение данных');

        Async::parallel([
            function ($callback) {
                $my = $callback;
                $oldName = Ide::accountManager()->getAccountData()['login'];

                Ide::service()->account()->changeLoginAsync($this->nameField->text, function (ServiceResponse $response) use ($callback, $oldName) {
                    if ($response->isNotSuccess()) {
                        if ($response->isFail()) {
                            list($message, $param) = str::split($response->result(), ':');

                            switch ($message) {
                                case 'LoginNotUnique':
                                    Notifications::error('Ошибка сохранения', "Данное имя занято другим пользователем.");
                                    break;
                                case "LoginMinLength":
                                    Notifications::error('Ошибка сохранения', 'Введенное имя слишком короткое, минимум символов - ' . $param);
                                    break;
                                case "LoginMaxLength":
                                    Notifications::error('Ошибка сохранения', 'Введенное имя слишком длинное, максимум символов - ' . $param);
                                    break;
                                default:
                                    Notifications::error('Ошибка сохранения', $message);
                                    break;
                            }

                        } else {
                            Notifications::show('Ошибка сохранения', 'Невозможно сохранить ваш псевдоним, возможно он введен некорректно!', 'ERROR');
                        }
                    }

                    if ($response->isSuccess() && $oldName != $this->nameField->text) {
                        Notifications::show('Псевдоним изменен', 'Поздравляем, ваш псевдоним был успешно изменен на - ' . $this->nameField->text, 'SUCCESS');
                    }

                    $callback();
                });
            },
            function ($callback) {
                if ($this->avatarChanged) {
                    if ($this->avatarFile) {
                        Ide::service()->file()->uploadAsync($this->avatarFile, function (ServiceResponse $response) use ($callback) {
                            if ($response->isSuccess()) {
                                Ide::service()->account()->changeAvatarAsync($response->result('id'), function (ServiceResponse $response) use ($callback) {
                                    if ($response->isNotSuccess()) {
                                        if ($response->isFail()) {
                                            Notifications::error('Ошибка сохранения', $response->message());
                                        } else {
                                            Notifications::show('Ошибка сохранения', 'Невозможно сохранить ваш аватар, попробуйте другой.', 'ERROR');
                                        }
                                    }

                                    if ($response->isSuccess()) {
                                        $this->avatarChanged = false;
                                        Notifications::show('Аватар изменен', 'Поздравляем, ваш аватар был успешно изменен на другой', 'SUCCESS');
                                    }

                                    $callback();
                                });
                            } else {
                                if ($response->isNotSuccess()) {
                                    Notifications::show('Ошибка сохранения', 'Невозможно сохранить ваш аватар, изображение не может быть загружено.', 'ERROR');
                                }

                                if ($response->isSuccess()) {
                                    $this->avatarChanged = false;
                                    Notifications::show('Аватар удален', 'Поздравляем, аватар вашего профиля был успешно удален', 'SUCCESS');
                                }

                                $callback();
                            }
                        });
                    } else {
                        Ide::service()->account()->deleteAvatarAsync(function (ServiceResponse $response) use ($callback) {
                            if ($response->isNotSuccess()) {
                                Notifications::show('Ошибка сохранения', 'Невозможно удалить ваш аватар, попробуйте в другой раз.', 'ERROR');
                            }

                            $callback();
                        });
                    }
                } else {
                    $callback();
                }
            }
        ], function () {
            Ide::accountManager()->updateAccount();

            $this->hidePreloader();
            $this->update();
        });
    }

    /**
     * @event avatarClearButton.action
     */
    public function doAvatarClear()
    {
        $this->avatarArea->image = Ide::get()->getImage('noAvatar.jpg')->image;
        $this->avatarFile = null;

        $this->avatarChanged = true;
    }

    /**
     * @event avatarEditButton.action
     */
    public function doAvatarEdit()
    {
        if ($file = $this->dialog->execute()) {
            $this->avatarArea->image = new UXImage(Stream::of($file));
            $this->avatarFile = $file;

            $this->avatarChanged = true;
        }
    }

    /**
     * @event changePasswordButton.action
     */
    public function doChangePassword()
    {
        $dialog = new AccountChangePasswordForm();
        $dialog->showAndWait();
    }

    /**
     * @event cancelButton.action
     */
    public function doCancel()
    {
        $this->hide();
    }
}