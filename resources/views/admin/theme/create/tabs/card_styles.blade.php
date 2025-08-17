<div class="row">
    <div class="col-12 col-md-6">

        @php
            $items = [
                'course',
                'product',
                'bundle',
                'upcoming_course',
                'blog_post',
                'instructor',
                'organization',
            ];

            $cardNames = ['grid_card_1'];
        @endphp

        @foreach($items as $item)
            <div class="form-group mb-3">
                <label class="input-label">{{ trans("update.{$item}") }}</label>
                <select name="contents[card_styles][{{ $item }}]" class="form-control">
                    <option value="">{{ trans('update.select_a_card_style') }}</option>

                    @foreach($cardNames as $cardName)
                        <option value="{{ $cardName }}" {{ (!empty($themeContents) and !empty($themeContents['card_styles']) and !empty($themeContents['card_styles'][$item]) and $themeContents['card_styles'][$item] == $cardName) ? 'selected' : '' }}>{{ trans("update.{$cardName}") }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
</div>
