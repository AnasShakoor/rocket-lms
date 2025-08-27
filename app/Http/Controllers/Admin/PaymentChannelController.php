<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentChannelRequest;
use App\Models\PaymentChannel;
use App\PaymentChannels\ChannelManager;
use Illuminate\Support\Facades\Log;

class PaymentChannelController extends Controller
{
    public function index()
    {
        $this->authorize('admin_payment_channel_list');

        $paymentChannels = PaymentChannel::orderBy('created_at', 'desc')->paginate(10);

        Log::info('Payment channels list accessed', [
            'count' => $paymentChannels->count(),
            'user_id' => auth()->id()
        ]);

        $data = [
            'pageTitle' => trans('admin/pages/paymentChannels.payment_channels'),
            'paymentChannels' => $paymentChannels
        ];

        return view('admin.settings.financial.payment_channel.lists', $data);
    }

    public function edit($id)
    {
        $this->authorize('admin_payment_channel_edit');

        $paymentChannel = PaymentChannel::findOrFail($id);
        $channel = ChannelManager::makeChannel($paymentChannel);
        $credentialItems = $channel->getCredentialItems();
        $showTestModeToggle = $channel->getShowTestModeToggle();

        Log::info('Payment channel edit page accessed', [
            'payment_channel_id' => $id,
            'class_name' => $paymentChannel->class_name,
            'status' => $paymentChannel->status,
            'credential_items' => $credentialItems,
            'show_test_mode_toggle' => $showTestModeToggle,
            'has_credentials' => !empty($paymentChannel->credentials),
            'currencies' => $paymentChannel->currencies,
            'user_id' => auth()->id()
        ]);

        $data = [
            'pageTitle' => trans('admin/pages/paymentChannels.payment_channel_edit'),
            'paymentChannel' => $paymentChannel,
            'credentialItems' => $credentialItems,
            'showTestModeToggle' => $showTestModeToggle,
        ];

        return view('admin.settings.financial.payment_channel.create', $data);
    }

    public function update(PaymentChannelRequest $request, $id)
    {
        $this->authorize('admin_payment_channel_edit');

        $data = $request->validated();
        $paymentChannel = PaymentChannel::findOrFail($id);

        Log::info('Payment channel update started', [
            'payment_channel_id' => $id,
            'class_name' => $paymentChannel->class_name,
            'old_status' => $paymentChannel->status,
            'new_status' => $data['status'],
            'old_credentials' => $paymentChannel->credentials,
            'new_credentials' => $data['credentials'] ?? null,
            'old_currencies' => $paymentChannel->currencies,
            'new_currencies' => $data['currencies'] ?? null,
            'user_id' => auth()->id()
        ]);

        $paymentChannel->update([
            'title' => $data['title'],
            'image' => $data['image'],
            'status' => $data['status'],
            'credentials' => !empty($data['credentials']) ? json_encode($data['credentials']) : null,
            'currencies' => !empty($data['currencies']) ? json_encode($data['currencies']) : null,
        ]);

        Log::info('Payment channel updated successfully', [
            'payment_channel_id' => $id,
            'class_name' => $paymentChannel->class_name,
            'new_status' => $data['status'],
            'has_credentials' => !empty($data['credentials']),
            'credentials_count' => !empty($data['credentials']) ? count($data['credentials']) : 0,
            'currencies_count' => !empty($data['currencies']) ? count($data['currencies']) : 0
        ]);

        return redirect(getAdminPanelUrl("/settings/payment_channels/{$paymentChannel->id}/edit"));
    }

    public function toggleStatus($id)
    {
        $this->authorize('admin_payment_channel_toggle_status');

        $channel = PaymentChannel::findOrFail($id);
        $oldStatus = $channel->status;
        $newStatus = ($channel->status == 'active') ? 'inactive' : 'active';

        Log::info('Payment channel status toggle', [
            'payment_channel_id' => $id,
            'class_name' => $channel->class_name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => auth()->id()
        ]);

        $channel->update([
            'status' => $newStatus
        ]);

        return redirect(getAdminPanelUrl() . '/settings/financial');
    }
}
