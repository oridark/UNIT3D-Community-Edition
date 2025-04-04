@extends('layout.default')

@section('title')
    <title>Donate - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Donate" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumb--active">捐赠</li>
@endsection

@section('content')
    <section x-data class="panelV2">
        <h2 class="panel__heading">捐赠 {{ config('other.title') }}</h2>
        <div class="panel__body">
            <p>{{ config('donation.description') }}</p>
            <div class="donation-packages">
                @foreach ($packages as $package)
                    <div class="donation-package__wrapper">
                        <div class="donation-package">
                            <div class="donation-package__header">
                                <div class="donation-package__name">{{ $package->name }}</div>
                                <div class="donation-package__price-days">
                                    <span class="donation-package__price">
                                        {{ $package->cost }} {{ config('donation.currency') }}
                                    </span>
                                    <span class="donation-package__separator">-</span>
                                    <span class="donation-package__days">
                                        @if ($package->donor_value === null)
                                            Lifetime
                                        @else
                                            {{ $package->donor_value }} Days
                                        @endif
                                    </span>
                                </div>
                                <div class="donation-package__description">
                                    {{ $package->description }}
                                </div>
                            </div>
                            <div class="donation-package__benefits-list">
                                <ol class="benefits-list">
                                    @if ($package->donor_value === null)
                                        <li>Unlimited Download Slots</li>
                                    @endif

                                    @if ($package->donor_value === null)
                                        <li>Custom User Icon</li>
                                    @endif

                                    <li>全站Free</li>
                                    <li>免疫HR (请勿滥用)</li>
                                    <li
                                        style="
                                            background-image: url(/img/sparkels.gif);
                                            width: auto;
                                        "
                                    >
                                        彩虹ID
                                    </li>
                                    <li>
                                        黄星
                                        @if ($package->donor_value === null)
                                            <i
                                                id="lifeline"
                                                class="fal fa-star"
                                                title="Lifetime Donor"
                                            ></i>
                                        @else
                                            <i class="fal fa-star text-gold" title="Donor"></i>
                                        @endif
                                    </li>
                                    <li>
                                        {{ config('other.title') }}的诚信感谢与祝福
                                    </li>
                                    @if ($package->upload_value !== null)
                                        <li>
                                            {{ App\Helpers\StringHelper::formatBytes($package->upload_value) }}
                                            上传
                                        </li>
                                    @endif

                                    @if ($package->bonus_value !== null)
                                        <li>
                                            {{ number_format($package->bonus_value) }} Bonus Points
                                        </li>
                                    @endif

                                    @if ($package->invite_value !== null)
                                        <li>{{ $package->invite_value }} 个邀请</li>
                                    @endif
                                </ol>
                            </div>
                            <div class="donation-package__footer">
                                <p class="form__group form__group--horizontal">
                                    <button
                                        class="form__button form__button--filled form__button--centered"
                                        x-on:click.stop="$refs.dialog{{ $package->id }}.showModal()"
                                    >
                                        <i class="fas fa-handshake"></i>
                                        捐赠
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @foreach ($packages as $package)
            <dialog class="dialog" x-ref="dialog{{ $package->id }}">
                <h4 class="dialog__heading">Donate $ {{ $package->cost }} USD</h4>
                <form
                    class="dialog__form"
                    method="POST"
                    action="{{ route('donations.store') }}"
                    x-on:click.outside="$refs.dialog{{ $package->id }}.close()"
                >
                    @csrf
                    <span class="text-success text-center">
                        按照以下步骤进行捐赠::
                    </span>
                    <div class="form__group--horizontal">
                        @foreach ($gateways->sortBy('position') as $gateway)
                            <p class="form__group">
                                <input
                                    class="form__text"
                                    type="text"
                                    disabled
                                    value="{{ $gateway->address }}"
                                    id="{{ 'gateway-' . $gateway->id }}"
                                />
                                <label
                                    for="{{ 'gateway-' . $gateway->id }}"
                                    class="form__label form__label--floating"
                                >
                                    {{ $gateway->name }}
                                </label>
                            </p>
                        @endforeach

                        <p class="text-info">
                            Send
                            <strong>
                                $ {{ $package->cost }} {{ config('donation.currency') }}
                            </strong>
                            to gateway of your choice. Take note of the tx hash, receipt number, etc
                            and input it below.
                        </p>
                    </div>
                    <div class="form__group--horizontal">
                        <p class="form__group">
                            <input
                                class="form__text"
                                type="text"
                                disabled
                                value="{{ $package->cost }}"
                                id="package-cost"
                            />
                            <label for="package-cost" class="form__label form__label--floating">
                                Cost
                            </label>
                        </p>
                        <p class="form__group">
                            <input
                                class="form__text"
                                type="text"
                                value=""
                                id="proof"
                                name="transaction"
                            />
                            <label for="proof" class="form__label form__label--floating">
                                Tx hash, Receipt number, Etc
                            </label>
                        </p>
                    </div>
                    <span class="text-warning">
                        * 有些途径的转账需要较长时间去核对，如果两天内没有结果请联系我们.
                    </span>
                    <p class="form__group">
                        <input type="hidden" name="package_id" value="{{ $package->id }}" />
                        <button class="form__button form__button--filled">确认</button>
                        <button
                            formmethod="dialog"
                            formnovalidate
                            class="form__button form__button--outlined"
                        >
                            {{ __('common.cancel') }}
                        </button>
                    </p>
                </form>
            </dialog>
        @endforeach
    </section>
@endsection
