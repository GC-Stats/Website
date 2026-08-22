<?php

use App\Mail\UserNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\EmailNotificationPreferences;
use Illuminate\Support\Facades\Mail;

test('the account settings page renders the email preferences form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.edit'))
        ->assertOk()
        ->assertSee(__('notifications.email_preferences.title'));
});

test('email notifications are opt-in: nothing is emailed until a category is enabled', function () {
    Mail::fake();

    $user = User::factory()->create();

    app(NotificationService::class)->notify(
        recipient: $user,
        type: NotificationService::TYPE_SANCTION_ISSUED,
        title: 'A sanction was issued',
        description: 'details',
    );

    Mail::assertNothingQueued();
});

test('updating email preferences persists on the user and gates emailing', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.notifications.email-preferences.update'), [
            'categories' => [EmailNotificationPreferences::CATEGORY_SANCTION],
        ])
        ->assertRedirect();

    expect(EmailNotificationPreferences::enabled($user->fresh(), EmailNotificationPreferences::CATEGORY_SANCTION))->toBeTrue()
        ->and(EmailNotificationPreferences::enabled($user->fresh(), EmailNotificationPreferences::CATEGORY_CHANGE_REQUEST))->toBeFalse();

    app(NotificationService::class)->notify(
        recipient: $user->fresh(),
        type: NotificationService::TYPE_SANCTION_ISSUED,
        title: 'A sanction was issued',
        description: 'details',
    );

    Mail::assertQueued(UserNotificationMail::class, fn ($mail) => $mail->hasTo($user->email));
});

test('disabling a previously enabled category stops emailing for it', function () {
    Mail::fake();

    $user = User::factory()->create();
    EmailNotificationPreferences::update($user, [EmailNotificationPreferences::CATEGORY_SANCTION]);

    EmailNotificationPreferences::update($user, []);

    app(NotificationService::class)->notify(
        recipient: $user->fresh(),
        type: NotificationService::TYPE_SANCTION_ISSUED,
        title: 'A sanction was issued',
        description: 'details',
    );

    Mail::assertNothingQueued();
});
