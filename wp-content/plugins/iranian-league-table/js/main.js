var url,
    sportType,
    leagueTitle,
    weightValue,
    borderWidthValue,
    borderColorValue,
    titleColorValue,
    titleBgColorValue,
    thColorValue,
    theadColorValue,
    tdColorValue,
    oddRowBgValue,
    evenRowBgValue,
    logoWidthValue,
    logoDisplayValue,
    teamsLinkValue,
    tableTitleShowValue,
    setLeagueValue = function () {
        (url = document.querySelector(".vaz3-table").getAttribute("data-url")),
            (sportType = document.querySelector(".vaz3-table").getAttribute("data-sportype")),
            (leagueTitle = document.querySelector(".vaz3-table").getAttribute("data-lt")),
            (weightValue = document.querySelector(".vaz3-table").getAttribute("data-wv")),
            (borderWidthValue = document.querySelector(".vaz3-table").getAttribute("data-bw")),
            (borderColorValue = document.querySelector(".vaz3-table").getAttribute("data-bc")),
            (titleColorValue = document.querySelector(".vaz3-table").getAttribute("data-tc")),
            (titleBgColorValue = document.querySelector(".vaz3-table").getAttribute("data-tbgc")),
            (thColorValue = document.querySelector(".vaz3-table").getAttribute("data-thc")),
            (theadColorValue = document.querySelector(".vaz3-table").getAttribute("data-thbgc")),
            (tdColorValue = document.querySelector(".vaz3-table").getAttribute("data-tdc")),
            (oddRowBgValue = document.querySelector(".vaz3-table").getAttribute("data-oddbgc")),
            (evenRowBgValue = document.querySelector(".vaz3-table").getAttribute("data-evbgc")),
            (logoWidthValue = document.querySelector(".vaz3-table").getAttribute("data-lw")),
            (logoDisplayValue = document.querySelector(".vaz3-table").getAttribute("data-ld")),
            (teamsLinkValue = document.querySelector(".vaz3-table").getAttribute("data-tlv")),
            (tableTitleShowValue = document.querySelector(".vaz3-table").getAttribute("data-tshv")),
            loadTable(url);
    },
    loadTable = function (t) {
        var e,
            l = t,
            a = document.querySelector(".vaz3-table");
        "Football" == sportType &&
            (a.innerHTML =
                ' <h3 style="margin:0 ; padding:5px 10px;" class="table-title"></h3><div class="vaz3-table-holder"><table style="width:100%; text-align:center;" class="vaz3-standing"><thead><tr><th scope="col">\u0631\u062a\u0628\u0647</th><th scope="col">\u062a\u06cc\u0645</th><th scope="col">\u0628\u0627\u0632\u06cc</th><th class="in-detailed" scope="col">\u0628\u0631\u062f</th><th class="in-detailed" scope="col">\u0645\u0633\u0627\u0648\u06cc</th><th class="in-detailed" scope="col">\u0628\u0627\u062e\u062a</th><th class="in-detailed" scope="col"> \u06af\u0644 -/+</th><th class="in-detailed" scope="col">\u062a\u0641\u0627\u0636\u0644 </th><th scope="col">\u0627\u0645\u062a\u064a\u0627\u0632</th></tr></thead><tbody></tbody></table></div>'),
            "Volleyball" == sportType &&
                (a.innerHTML =
                    ' <h3 style="margin:0 ; padding:5px 10px;" class="table-title"></h3><div class="vaz3-table-holder"><table style="width:100%; text-align:center;" class="vaz3-standing"><thead><tr class="main-row"><th scope="col"></th><th scope="col"></th><th scope="col"></th><th class="has-border" scope="col" colspan="3">\u0645\u0633\u0627\u0628\u0642\u0627\u062a</th><th class="has-border" scope="col" colspan="6">\u062c\u0632\u0626\u06cc\u0627\u062a \u0646\u062a\u0627\u06cc\u062c</th><th class="has-border" scope="col" colspan="3">\u0633\u062a</th><th class="has-border" scope="col" colspan="3">\u067e\u0648\u0626\u0646</th></tr><tr class="secondary-row"><th>\u0631\u062a\u0628\u0647</th><th></th><th>\u0627\u0645\u062a\u06cc\u0627\u0632</th><th scope="col">\u0628\u0627\u0632\u06cc</th><th scope="col">\u0628\u0631\u062f</th><th scope="col">\u0628\u0627\u062e\u062a</th><th scope="col">3 - 0</th><th scope="col">3 - 1</th><th scope="col">3 - 2</th><th scope="col">0 - 3</th><th scope="col">1 - 3</th><th scope="col">2 - 3</th><th scope="col">\u0628\u0631\u062f\u0647</th><th scope="col">\u0628\u0627\u062e\u062a\u0647</th><th scope="col">\u0645\u0639\u062f\u0644</th><th scope="col">\u0628\u0631\u062f\u0647</th><th scope="col">\u0628\u0627\u062e\u062a\u0647</th><th scope="col">\u0645\u0639\u062f\u0644</th></tr</thead><tbody></tbody></table></div>');
        const o = new XMLHttpRequest();
        (o.onload = function () {
            (e = JSON.parse(this.response)), afterAjaxSuccess(e);
        }),
            o.open("GET", l),
            o.send();
    },
    afterAjaxSuccess = function (t) {
        var e,
            l,
            a = document.querySelector(".vaz3-table tbody"),
            o = document.querySelector(".vaz3-table");
        (e = t.title ? t.title : ""),
            (l = t.link ? t.link : ""),
            o.insertAdjacentHTML(
                "afterend",
                '<a style="font-size: 11px; color: #666; display: block; text-align: right; margin-top: 7px; " href=" ' +
                    l +
                    ' ">\u0641\u062f\u0631\u062a \u06af\u0631\u0641\u062a\u0647 \u0627\u0632 \u062c\u062f\u0648\u0644 ' +
                    e +
                    " \u0648\u0631\u0632\u0634 \u0633\u0647</a>"
            ),
            t.teams.forEach((t, e) => {
                var l;
                (l = t.link ? t.link : ""),
                    "Football" == sportType &&
                        a.insertAdjacentHTML(
                            "beforeend",
                            "<tr><td> " +
                                (e + 1) +
                                ' </td><td style="text-align:right;"><figure><img style="max-width:100%" src="' +
                                t.logo +
                                '"></figure>' +
                                t.name +
                                "</td><td>" +
                                t.played +
                                '</td><td class="in-detailed">' +
                                t.wins +
                                '</td><td class="in-detailed">' +
                                t.draws +
                                '</td><td class="in-detailed">' +
                                t.losses +
                                '</td><td class="in-detailed">' +
                                t.goalFor +
                                "-" +
                                t.goalAgainst +
                                '</td><td class="in-detailed">' +
                                t.goalDifference +
                                "</td><td>" +
                                t.points +
                                "</td></tr>"
                        ),
                    "Volleyball" == sportType &&
                        a.insertAdjacentHTML(
                            "beforeend",
                            "<tr><td>" +
                                (e + 1) +
                                ' </td><td style="text-align:right;" class="has-left-border" scope="row"><span><figure><img style="max-width:100%" src="' +
                                t.logo +
                                '"></figure>' +
                                t.name +
                                '</span></td><td class="has-left-border">' +
                                t.points +
                                "</td><td>" +
                                t.played +
                                "</td><td>" +
                                t.wins +
                                '</td><td class="has-left-border">' +
                                t.losses +
                                "</td><td>" +
                                t.wins30 +
                                "</td><td>" +
                                t.wins31 +
                                "</td><td>" +
                                t.wins32 +
                                "</td><td>" +
                                t.losses30 +
                                "</td><td>" +
                                t.losses31 +
                                '</td><td class="has-left-border">' +
                                t.losses32 +
                                "</td><td>" +
                                t.setWins +
                                "</td><td>" +
                                t.setLosses +
                                '</td><td class="has-left-border">' +
                                t.setAverage +
                                "</td><td>" +
                                t.totalPointsScored +
                                "</td><td>" +
                                t.totalPointsConceded +
                                "</td><td>" +
                                t.totalPointsAverage +
                                "</td></tr>"
                        );
            });
        for (var d = document.querySelectorAll(".vaz3-table .table-title"), r = 0; r < d.length; r++)
            "false" == tableTitleShowValue ? (d[r].style.display = "none") : ((d[r].style.display = "block"), (d[r].innerHTML = leagueTitle), (d[r].style.color = titleColorValue), (d[r].style.backgroundColor = titleBgColorValue));
        var s = document.querySelectorAll(".vaz3-standing th");
        for (r = 0; r < s.length; r++) s[r].style.color = thColorValue;
        var c = document.querySelectorAll(".vaz3-standing thead tr");
        for (r = 0; r < c.length; r++) c[r].style.backgroundColor = theadColorValue;
        var n = document.querySelectorAll(".vaz3-standing td");
        for (r = 0; r < n.length; r++) n[r].style.color = tdColorValue;
        var i = document.querySelectorAll(".vaz3-standing tbody tr:nth-child(odd)");
        for (r = 0; r < i.length; r++) i[r].style.backgroundColor = oddRowBgValue;
        var u = document.querySelectorAll(".vaz3-standing tbody tr:nth-child(even)");
        for (r = 0; r < u.length; r++) u[r].style.backgroundColor = evenRowBgValue;
        var h = document.querySelectorAll(".vaz3-standing td figure");
        for (r = 0; r < h.length; r++) h[r].style.width = logoWidthValue;
        var g = document.querySelectorAll(".vaz3-standing .in-detailed");
        for (r = 0; r < g.length; r++) g[r].style.display = "basic" == weightValue ? "none" : "table-cell";
        var b = document.querySelectorAll(".vaz3-standing td figure");
        for (r = 0; r < b.length; r++) "false" == logoDisplayValue ? (b[r].style.display = "none") : ((b[r].style.display = "inline-block"), (b[r].style.width = logoWidthValue + "px"), (b[r].style.height = logoWidthValue + "px"));
    };
setLeagueValue();
