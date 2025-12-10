<script type="text/javascript">
    function confirmDel(url) {
        if (confirm("<?= __('GLOBAL__CONFIRM_DELETE') ?>"))
            window.location.href = '' + url + '';
        else
            return false;
    }
</script>
