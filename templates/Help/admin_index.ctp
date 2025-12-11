<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('HELP__CHOOSE_QUESTION') ?></h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="questions"><?= __('HELP__CHOOSE_QUESTION') ?></label>
                        <select
                            id="questions"
                            class="form-control"
                            aria-describedby="questions-help"
                        >
                            <option value=""><?= __('HELP__CHOOSE_QUESTION') ?></option>
                        </select>
                        <small id="questions-help" class="form-text text-muted">
                            <?= __('HELP__CHOOSE_QUESTION') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('HELP__PAGE_EXPLAIN_TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <blockquote cite="http://mineweb.org">
                        <?= __('HELP__PAGE_EXPLAIN_CONTENT') ?>
                    </blockquote>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('HELP__ANSWER_TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <div id="answers">
                        <blockquote id="answers-placeholder">
                            <small><i><?= __('HELP__CHOOSE_QUESTION') ?></i></small>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('HELP__POST_TICKET_TITLE_BOX') ?></h3>
                </div>
                <div class="card-body">
                    <p><?= __('HELP__POST_TICKET_EXPLAIN') ?></p>

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'help_submit_ticket'],
                        'data-ajax' => 'true',
                        'data-callback' => 'afterSubmitTicket',
                    ]) ?>

                    <div class="form-group">
                        <label for="ticket-title"><?= __('HELP__POST_TICKET_TITLE') ?></label>
                        <input
                            id="ticket-title"
                            type="text"
                            class="form-control"
                            name="title"
                            placeholder="Problème de..."
                            autocomplete="off"
                        >
                    </div>

                    <div class="form-group">
                        <label for="ticket-content"><?= __('HELP__POST_TICKET_CONTENT') ?></label>
                        <textarea
                            id="ticket-content"
                            class="form-control"
                            name="content"
                            rows="4"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-info">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    function afterSubmitTicket(data) {
    }

    document.addEventListener("DOMContentLoaded", function () {
        let questionsSelect = document.getElementById("questions");
        let answersContainer = document.getElementById("answers");
        let placeholder = document.getElementById("answers-placeholder");

        if (!questionsSelect || !answersContainer) {
            return;
        }

        function hideAllAnswers() {
            let answerBlocks = answersContainer.querySelectorAll("div[data-question-id]");
            answerBlocks.forEach(function (block) {
                block.style.display = "none";
            });
        }

        function handleQuestionChange() {
            let selectedId = questionsSelect.value;
            hideAllAnswers();

            if (placeholder) {
                placeholder.style.display = selectedId ? "none" : "block";
            }

            if (!selectedId) {
                return;
            }

            let target = answersContainer.querySelector('div[data-question-id="' + selectedId + '"]');
            if (target) {
                target.style.display = "block";
            }
        }

        questionsSelect.addEventListener("change", handleQuestionChange);

        fetch("<?= $this->Url->build(['_name' => 'help_get_questions_and_answers']) ?>", {
            method: "GET",
            headers: {
                "Accept": "application/json"
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!Array.isArray(data)) {
                    return;
                }

                questionsSelect.innerHTML = "";

                let defaultOption = document.createElement("option");
                defaultOption.value = "";
                defaultOption.textContent = "<?= addslashes(__('HELP__CHOOSE_QUESTION')) ?>";
                questionsSelect.appendChild(defaultOption);

                data.forEach(function (item) {
                    let option = document.createElement("option");
                    option.value = item.id;
                    option.textContent = item.question;
                    questionsSelect.appendChild(option);

                    let answer = document.createElement("div");
                    answer.setAttribute("data-question-id", item.id);
                    answer.style.display = "none";
                    answer.innerHTML = item.answer;
                    answersContainer.appendChild(answer);
                });

                if (placeholder) {
                    placeholder.style.display = "block";
                }
            })
            .catch(function (error) {
                console.error(error);
            });
    });
</script>
