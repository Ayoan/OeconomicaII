import os
import sys
from unittest.mock import MagicMock

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from notify.line_bot import build_structure_error_message, build_summary_message, send_line_message  # noqa: E402

CONFIG = {'LINE_BOT_CONFIG': {'CHANNEL_ACCESS_TOKEN': 'dummy-token', 'USER_ID': 'dummy-user'}}


def test_build_summary_message_no_errors():
    message = build_summary_message(inserted=5, errors=[])
    assert '新規登録: 5件' in message
    assert 'エラーなし' in message


def test_build_summary_message_with_errors_and_skipped_sources():
    message = build_summary_message(inserted=3, errors=['err1', 'err2'], skipped_sources=['e-navi'])
    assert '新規登録: 3件' in message
    assert 'エラー: 2件' in message
    assert 'e-navi' in message


def test_build_structure_error_message_includes_source_and_detail():
    message = build_structure_error_message('SMBC', '要素が見つかりません: //*[@id=\'x\']')
    assert 'SMBC' in message
    assert "//*[@id='x']" in message


def test_send_line_message_posts_with_bearer_token_and_payload():
    session = MagicMock()
    response = MagicMock()
    session.post.return_value = response

    send_line_message('テストメッセージ', CONFIG, session=session)

    args, kwargs = session.post.call_args
    assert args[0] == 'https://api.line.me/v2/bot/message/push'
    assert kwargs['headers']['Authorization'] == 'Bearer dummy-token'
    assert kwargs['json']['to'] == 'dummy-user'
    assert kwargs['json']['messages'] == [{'type': 'text', 'text': 'テストメッセージ'}]
    response.raise_for_status.assert_called_once()


def test_send_line_message_raises_on_http_error():
    session = MagicMock()
    response = MagicMock()
    response.raise_for_status.side_effect = RuntimeError('HTTP 401')
    session.post.return_value = response

    try:
        send_line_message('テスト', CONFIG, session=session)
        assert False, "例外が送出されるべき"
    except RuntimeError:
        pass
