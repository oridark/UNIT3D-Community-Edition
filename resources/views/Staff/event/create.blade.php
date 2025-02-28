@extends('layout.default')

@section('breadcrumbs')
    <li class="breadcrumbV2">
        <a href="{{ route('staff.dashboard.index') }}" class="breadcrumb__link">
            {{ __('staff.staff-dashboard') }}
        </a>
    </li>
    <li class="breadcrumbV2">
        <a href="{{ route('staff.events.index') }}" class="breadcrumb__link">
            {{ __('event.events') }}
        </a>
    </li>
    <li class="breadcrumb--active">
        {{ __('common.new-adj') }}
    </li>
@endsection

@section('page', 'page__event--index')

@section('main')
    <section class="panelV2">
        <h2 class="panel__heading">{{ __('event.add-event') }}</h2>
        <form class="dialog__form" method="POST" action="{{ route('staff.events.store') }}">
            @csrf
            <p class="form__group">
                <input
                    id="name"
                    class="form__text"
                    type="text"
                    autocomplete="off"
                    name="name"
                    required
                />
                <label class="form__label form__label--floating" for="name">
                    {{ __('common.name') }}
                </label>
            </p>
            <p class="form__group">
                <textarea
                    id="description"
                    class="form__textarea"
                    name="description"
                    required
                ></textarea>
                <label class="form__label form__label--floating" for="description">
                    {{ __('common.description') }}
                </label>
            </p>
            <p class="form__group">
                <input
                    id="icon"
                    class="form__text"
                    type="text"
                    autocomplete="off"
                    name="icon"
                    required
                />
                <label class="form__label form__label--floating" for="icon">
                    {{ __('common.icon') }}
                </label>
            </p>
            <div class="form__group--horizontal">
                <p class="form__group">
                    <input id="starts_at" class="form__text" name="starts_at" type="date" />
                    <label class="form__label form__label--floating" for="starts_at">
                        {{ __('event.starts-at') }}
                    </label>
                </p>
                <p class="form__group">
                    <input id="ends_at" class="form__text" name="ends_at" type="date" />
                    <label class="form__label form__label--floating" for="ends_at">
                        {{ __('event.ends-at') }}
                    </label>
                </p>
            </div>
            <p class="form__group">
                <input type="hidden" name="active" value="0" />
                <input
                    type="checkbox"
                    class="form__checkbox"
                    id="active"
                    name="active"
                    value="1"
                    @checked(old('active'))
                />
                <label class="form__label" for="active">{{ __('common.active') }}?</label>
            </p>
            <p class="form__group">
                <button class="form__button form__button--filled" wire:click="store">
                    {{ __('common.save') }}
                </button>
                <button
                    formmethod="dialog"
                    formnovalidate
                    class="form__button form__button--outlined"
                >
                    {{ __('common.cancel') }}
                </button>
            </p>
        </form>
    </section>
@endsection
