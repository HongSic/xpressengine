@section('page_title')
    <h2>{{ xe_trans('xe::addExtension') }}</h2>
@stop

@section('page_description')
    <small>{{ xe_trans('xe::addExtensionDescription') }}</small>
@endsection

<div class="row">
    <div class="col-sm-12">
        <form method="get" action="{{route('settings.extension.install')}}">
            <input type="hidden" value="{{Request::get('sale_type', 'free')}}" name="sale_type">
            <div class="panel-heading">
                <div class="search-group-box">
                    <div class="input-group search-group">
                        <div class="input-group-btn">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="selected-type">
                                    @if (\Request::has('tags'))
                                        {{xe_trans('xe::tag')}}
                                    @elseif (\Request::has('authors'))
                                        {{xe_trans('xe::creatorName')}}
                                    @else
                                        {{xe_trans('xe::keyword')}}
                                    @endif
                                </span>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li>
                                    <a href="#" data-target="query"><span>{{xe_trans('xe::keyword')}}</span></a>
                                </li>
                                <li>
                                    <a href="#" data-target="authors"><span>{{xe_trans('xe::creatorName')}}</span></a>
                                </li>
                                <li>
                                    <a href="#" data-target="tags"><span>{{xe_trans('xe::tag')}}</span></a>
                                </li>
                            </ul>
                        </div>
                        <div class="search-input-group">
                            <input type="text" class="form-control" placeholder="검색어를 입력하세요" name="query"
                                @if (Request::has('query'))
                                    value="{{Request::get('query')}}" name="query"
                                @elseif (Request::has('authors'))
                                    value="{{Request::get('authors')}}" name="authors"
                                @elseif (Request::has('tags'))
                                    value="{{Request::get('tags')}}" name="tags"
                                @endif>
                            <button class="btn-link">
                                <i class="xi-search"></i><span class="sr-only">검색</span>
                            </button>
                        </div>
                    </div>
                    @if (Request::get('sale_type') == 'my_site')
                        <button type="button" class="xu-button xu-button--default admin-button--active">익스텐션 업로드</button>
                    @endif
                </div>
            </div>

            <div class="panel admin-tab">
                <button class="admin-tab-left" style="display:none"><i class="xi-angle-left"></i><span class="xe-sr-only">처음으로 이동</span></button>
                <ul class="admin-tab-list">
                    <li class="free @if (Request::get('sale_type', 'free') == 'free') on @endif"><a href="{{ route('settings.extension.install', ['sale_type' => 'free']) }}">{{ xe_trans('xe::무료') }} <span class="xe-badge xe-primary">{{$typeCounts['free']}}</span></a></li>
                    <li class="charge @if (Request::get('sale_type') == 'charge') on @endif"><a href="{{ route('settings.extension.install', ['sale_type' => 'charge']) }}">{{ xe_trans('xe::유료') }} <span class="xe-badge xe-primary">{{$typeCounts['charge']}}</span></a></li>
                    <li class="my_site @if (Request::get('sale_type') == 'my_site') on @endif"><a href="{{ route('settings.extension.install', ['sale_type' => 'my_site']) }}">{{ xe_trans('xe::mySiteExtension') }} <span class="xe-badge xe-primary">{{$typeCounts['mySite']}}</span></a></li>
                </ul>
                <button class="admin-tab-right"><i class="xi-angle-right"></i><span class="xe-sr-only">끝으로 이동</span></button>
            </div>

            <h3 class="blind">테마 카드 리스트</h3>
            <div class="clearfix">
                <div class="pull-right">
                    <div class="dropdown" style="display: inline-block">
                        <button type="button" class="btn btn-default--transparent dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                            <span class="__xe_text">카테고리</span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu" style="overflow: auto; height: 200px;">
                            <li><a href="{{route('settings.extension.install')}}">전체</a></li>
                            @foreach ($extensionCategories as $value => $category)
                                <li @if (Request::get('category') == $value) class="active" @endif><a href="{{route('settings.extension.install', ['category' => $value])}}">{{$category}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown" style="display: inline-block">
                        <button type="button" class="btn btn-default--transparent dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                            <span class="__xe_text">정렬</span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu" style="overflow: auto; height: 200px;">
                            @foreach ($orderTypes as $idx => $value)
                                <li @if (Request::get('order_key') == $idx) class="active" @endif><a href="{{route('settings.extension.install', ['order_key' => $idx])}}">{{$value['name']}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        @if ($extensions->count() > 0)
            <ul class="list-group list-card list-extension">
                @foreach ($extensions as $idx => $extension)
{{--                     TODO 내가 구매한 플러그인 수정--}}
                    @if (Request::get('sale_type') == 'my_site')
                        @if ($extension->is_purchased = true)
                            @include($_skin::view('extension.item'))
                        @endif
                    @else
                        @include($_skin::view('extension.item'))
                    @endif
                @endforeach
            </ul>

            <div>
                {{$extensions->render()}}
            </div>
        @else
            @php
                $message = '아직 등록된 익스텐션이 없습니다!';
            @endphp
            @include($_skin::view('empty'))
        @endif
    </div>
</div>

<script>
    $(function(){
        $(document).on('click','.plugin-install',function(){
            $("#xe-install-plugin").find('[name="pluginId[]"]').val($(this).data('target'));
            $("#xe-install-plugin").submit();
        })
        $(document).on('click','.search-group li a',function(){
            $(".search-group").find('input[type="text"]').attr('name', $(this).data('target'));
            $(".search-group").find('.selected-type').text($(this).text());
        })
    });
</script>
