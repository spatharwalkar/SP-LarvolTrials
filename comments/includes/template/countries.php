<?php
/*
Copyright © 2009-2013 Commentics Development Team [commentics.org]
License: GNU General Public License v3.0
		 http://www.commentics.org/license/

This file is part of Commentics.

Commentics is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Commentics is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Commentics. If not, see <http://www.gnu.org/licenses/>.

Text to help preserve UTF-8 file encoding: 汉语漢語.
*/

if (!defined('IN_COMMENTICS')) { die('Access Denied.'); }

$cmtx_countries = '<select name="cmtx_country" class="cmtx_country_field" title="' . cmtx_define(CMTX_TITLE_COUNTRY) . '">
<option value="">' . cmtx_define(CMTX_TOP_COUNTRY)  . '</option>
<option value=""></option>
<option value="' . cmtx_define(CMTX_COUNTRY_US) . '">' . cmtx_define(CMTX_COUNTRY_US)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_UK) . '">' . cmtx_define(CMTX_COUNTRY_UK)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_IRELAND) . '">' . cmtx_define(CMTX_COUNTRY_IRELAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CANADA) . '">' . cmtx_define(CMTX_COUNTRY_CANADA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_AUSTRALIA) . '">' . cmtx_define(CMTX_COUNTRY_AUSTRALIA)  . '</option>
<option value=""></option>
<option value="' . cmtx_define(CMTX_COUNTRY_AFGHANISTAN) . '">' . cmtx_define(CMTX_COUNTRY_AFGHANISTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ALBANIA) . '">' . cmtx_define(CMTX_COUNTRY_ALBANIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ALGERIA) . '">' . cmtx_define(CMTX_COUNTRY_ALGERIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ANDORRA) . '">' . cmtx_define(CMTX_COUNTRY_ANDORRA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ANGOLA) . '">' . cmtx_define(CMTX_COUNTRY_ANGOLA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ANTIGUA) . '">' . cmtx_define(CMTX_COUNTRY_ANTIGUA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ARGENTINA) . '">' . cmtx_define(CMTX_COUNTRY_ARGENTINA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ARMENIA) . '">' . cmtx_define(CMTX_COUNTRY_ARMENIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ARUBA) . '">' . cmtx_define(CMTX_COUNTRY_ARUBA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_AUSTRIA) . '">' . cmtx_define(CMTX_COUNTRY_AUSTRIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_AZERBAIJAN) . '">' . cmtx_define(CMTX_COUNTRY_AZERBAIJAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BAHAMAS) . '">' . cmtx_define(CMTX_COUNTRY_BAHAMAS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BAHRAIN) . '">' . cmtx_define(CMTX_COUNTRY_BAHRAIN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BANGLADESH) . '">' . cmtx_define(CMTX_COUNTRY_BANGLADESH)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BARBADOS) . '">' . cmtx_define(CMTX_COUNTRY_BARBADOS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BARBUDA) . '">' . cmtx_define(CMTX_COUNTRY_BARBUDA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BELARUS) . '">' . cmtx_define(CMTX_COUNTRY_BELARUS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BELGIUM) . '">' . cmtx_define(CMTX_COUNTRY_BELGIUM)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BELIZE) . '">' . cmtx_define(CMTX_COUNTRY_BELIZE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BENIN) . '">' . cmtx_define(CMTX_COUNTRY_BENIN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BERMUDA) . '">' . cmtx_define(CMTX_COUNTRY_BERMUDA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BHUTAN) . '">' . cmtx_define(CMTX_COUNTRY_BHUTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BOLIVIA) . '">' . cmtx_define(CMTX_COUNTRY_BOLIVIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BOSNIA) . '">' . cmtx_define(CMTX_COUNTRY_BOSNIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BOTSWANA) . '">' . cmtx_define(CMTX_COUNTRY_BOTSWANA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BRAZIL) . '">' . cmtx_define(CMTX_COUNTRY_BRAZIL)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BRUNEI) . '">' . cmtx_define(CMTX_COUNTRY_BRUNEI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BULGARIA) . '">' . cmtx_define(CMTX_COUNTRY_BULGARIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BURKINA_FASO) . '">' . cmtx_define(CMTX_COUNTRY_BURKINA_FASO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BURMA) . '">' . cmtx_define(CMTX_COUNTRY_BURMA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_BURUNDI) . '">' . cmtx_define(CMTX_COUNTRY_BURUNDI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CAMBODIA) . '">' . cmtx_define(CMTX_COUNTRY_CAMBODIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CAMEROON) . '">' . cmtx_define(CMTX_COUNTRY_CAMEROON)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CAYMAN_ISLANDS) . '">' . cmtx_define(CMTX_COUNTRY_CAYMAN_ISLANDS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CENTRAL_AFRICAN_REP) . '">' . cmtx_define(CMTX_COUNTRY_CENTRAL_AFRICAN_REP)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CHAD) . '">' . cmtx_define(CMTX_COUNTRY_CHAD)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CHILE) . '">' . cmtx_define(CMTX_COUNTRY_CHILE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CHINA) . '">' . cmtx_define(CMTX_COUNTRY_CHINA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_COLOMBIA) . '">' . cmtx_define(CMTX_COUNTRY_COLOMBIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CONGO) . '">' . cmtx_define(CMTX_COUNTRY_CONGO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_COSTA_RICA) . '">' . cmtx_define(CMTX_COUNTRY_COSTA_RICA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CROATIA) . '">' . cmtx_define(CMTX_COUNTRY_CROATIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CUBA) . '">' . cmtx_define(CMTX_COUNTRY_CUBA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CYPRUS) . '">' . cmtx_define(CMTX_COUNTRY_CYPRUS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_CZECH_REPUBLIC) . '">' . cmtx_define(CMTX_COUNTRY_CZECH_REPUBLIC)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_DENMARK) . '">' . cmtx_define(CMTX_COUNTRY_DENMARK)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_DJIBOUTI) . '">' . cmtx_define(CMTX_COUNTRY_DJIBOUTI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_DOMINICAN_REP) . '">' . cmtx_define(CMTX_COUNTRY_DOMINICAN_REP)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_DR_CONGO) . '">' . cmtx_define(CMTX_COUNTRY_DR_CONGO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ECUADOR) . '">' . cmtx_define(CMTX_COUNTRY_ECUADOR)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_EGYPT) . '">' . cmtx_define(CMTX_COUNTRY_EGYPT)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_EL_SALVADOR) . '">' . cmtx_define(CMTX_COUNTRY_EL_SALVADOR)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_EQUATORIAL_GUINEA) . '">' . cmtx_define(CMTX_COUNTRY_EQUATORIAL_GUINEA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ERITREA) . '">' . cmtx_define(CMTX_COUNTRY_ERITREA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ESTONIA) . '">' . cmtx_define(CMTX_COUNTRY_ESTONIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ETHIOPIA) . '">' . cmtx_define(CMTX_COUNTRY_ETHIOPIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_FALKLANDS) . '">' . cmtx_define(CMTX_COUNTRY_FALKLANDS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_FAROE_ISLANDS) . '">' . cmtx_define(CMTX_COUNTRY_FAROE_ISLANDS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_FIJI) . '">' . cmtx_define(CMTX_COUNTRY_FIJI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_FINLAND) . '">' . cmtx_define(CMTX_COUNTRY_FINLAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_FRANCE) . '">' . cmtx_define(CMTX_COUNTRY_FRANCE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GABON) . '">' . cmtx_define(CMTX_COUNTRY_GABON)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GAMBIA) . '">' . cmtx_define(CMTX_COUNTRY_GAMBIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GEORGIA) . '">' . cmtx_define(CMTX_COUNTRY_GEORGIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GERMANY) . '">' . cmtx_define(CMTX_COUNTRY_GERMANY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GHANA) . '">' . cmtx_define(CMTX_COUNTRY_GHANA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GREECE) . '">' . cmtx_define(CMTX_COUNTRY_GREECE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GREENLAND) . '">' . cmtx_define(CMTX_COUNTRY_GREENLAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GRENADA) . '">' . cmtx_define(CMTX_COUNTRY_GRENADA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUADELOUPE) . '">' . cmtx_define(CMTX_COUNTRY_GUADELOUPE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUATEMALA) . '">' . cmtx_define(CMTX_COUNTRY_GUATEMALA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUERNSEY) . '">' . cmtx_define(CMTX_COUNTRY_GUERNSEY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUINEA) . '">' . cmtx_define(CMTX_COUNTRY_GUINEA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUINEA_BISSAU) . '">' . cmtx_define(CMTX_COUNTRY_GUINEA_BISSAU)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_GUYANA) . '">' . cmtx_define(CMTX_COUNTRY_GUYANA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_HAITI) . '">' . cmtx_define(CMTX_COUNTRY_HAITI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_HOLLAND) . '">' . cmtx_define(CMTX_COUNTRY_HOLLAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_HONDURAS) . '">' . cmtx_define(CMTX_COUNTRY_HONDURAS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_HONG_KONG) . '">' . cmtx_define(CMTX_COUNTRY_HONG_KONG)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_HUNGARY) . '">' . cmtx_define(CMTX_COUNTRY_HUNGARY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ICELAND) . '">' . cmtx_define(CMTX_COUNTRY_ICELAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_INDIA) . '">' . cmtx_define(CMTX_COUNTRY_INDIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_INDONESIA) . '">' . cmtx_define(CMTX_COUNTRY_INDONESIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_IRAN) . '">' . cmtx_define(CMTX_COUNTRY_IRAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_IRAQ) . '">' . cmtx_define(CMTX_COUNTRY_IRAQ)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ISLE_OF_MAN) . '">' . cmtx_define(CMTX_COUNTRY_ISLE_OF_MAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ISRAEL) . '">' . cmtx_define(CMTX_COUNTRY_ISRAEL)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ITALY) . '">' . cmtx_define(CMTX_COUNTRY_ITALY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_IVORY_COAST) . '">' . cmtx_define(CMTX_COUNTRY_IVORY_COAST)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_JAMAICA) . '">' . cmtx_define(CMTX_COUNTRY_JAMAICA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_JAPAN) . '">' . cmtx_define(CMTX_COUNTRY_JAPAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_JERSEY) . '">' . cmtx_define(CMTX_COUNTRY_JERSEY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_JORDON) . '">' . cmtx_define(CMTX_COUNTRY_JORDON)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_KAZAKHSTAN) . '">' . cmtx_define(CMTX_COUNTRY_KAZAKHSTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_KENYA) . '">' . cmtx_define(CMTX_COUNTRY_KENYA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_KUWAIT) . '">' . cmtx_define(CMTX_COUNTRY_KUWAIT)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_KYRGYZSTAN) . '">' . cmtx_define(CMTX_COUNTRY_KYRGYZSTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LAOS) . '">' . cmtx_define(CMTX_COUNTRY_LAOS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LATVIA) . '">' . cmtx_define(CMTX_COUNTRY_LATVIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LEBANON) . '">' . cmtx_define(CMTX_COUNTRY_LEBANON)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LESOTHO) . '">' . cmtx_define(CMTX_COUNTRY_LESOTHO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LIBERIA) . '">' . cmtx_define(CMTX_COUNTRY_LIBERIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LIBYA) . '">' . cmtx_define(CMTX_COUNTRY_LIBYA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LIECHTENSTEIN) . '">' . cmtx_define(CMTX_COUNTRY_LIECHTENSTEIN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LITHUANIA) . '">' . cmtx_define(CMTX_COUNTRY_LITHUANIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_LUXEMBOURG) . '">' . cmtx_define(CMTX_COUNTRY_LUXEMBOURG)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MACEDONIA) . '">' . cmtx_define(CMTX_COUNTRY_MACEDONIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MADAGASCAR) . '">' . cmtx_define(CMTX_COUNTRY_MADAGASCAR)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MALAWI) . '">' . cmtx_define(CMTX_COUNTRY_MALAWI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MALAYSIA) . '">' . cmtx_define(CMTX_COUNTRY_MALAYSIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MALAYSIA) . '">' . cmtx_define(CMTX_COUNTRY_MALAYSIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MALI) . '">' . cmtx_define(CMTX_COUNTRY_MALI)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MALTA) . '">' . cmtx_define(CMTX_COUNTRY_MALTA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MAURITANIA) . '">' . cmtx_define(CMTX_COUNTRY_MAURITANIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MAURITIUS) . '">' . cmtx_define(CMTX_COUNTRY_MAURITIUS)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MEXICO) . '">' . cmtx_define(CMTX_COUNTRY_MEXICO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MOLDOVA) . '">' . cmtx_define(CMTX_COUNTRY_MOLDOVA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MONACO) . '">' . cmtx_define(CMTX_COUNTRY_MONACO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MONGOLIA) . '">' . cmtx_define(CMTX_COUNTRY_MONGOLIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MONTENEGRO) . '">' . cmtx_define(CMTX_COUNTRY_MONTENEGRO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MOROCCO) . '">' . cmtx_define(CMTX_COUNTRY_MOROCCO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_MOZAMBIQUE) . '">' . cmtx_define(CMTX_COUNTRY_MOZAMBIQUE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NAMIBIA) . '">' . cmtx_define(CMTX_COUNTRY_NAMIBIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NEPAL) . '">' . cmtx_define(CMTX_COUNTRY_NEPAL)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NEW_ZEALAND) . '">' . cmtx_define(CMTX_COUNTRY_NEW_ZEALAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NICARAGUA) . '">' . cmtx_define(CMTX_COUNTRY_NICARAGUA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NIGER) . '">' . cmtx_define(CMTX_COUNTRY_NIGER)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NIGERIA) . '">' . cmtx_define(CMTX_COUNTRY_NIGERIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NORTH_KOREA) . '">' . cmtx_define(CMTX_COUNTRY_NORTH_KOREA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_NORWAY) . '">' . cmtx_define(CMTX_COUNTRY_NORWAY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_OMAN) . '">' . cmtx_define(CMTX_COUNTRY_OMAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PAKISTAN) . '">' . cmtx_define(CMTX_COUNTRY_PAKISTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PALESTINE) . '">' . cmtx_define(CMTX_COUNTRY_PALESTINE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PANAMA) . '">' . cmtx_define(CMTX_COUNTRY_PANAMA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PAPUA_NEW_GUINEA) . '">' . cmtx_define(CMTX_COUNTRY_PAPUA_NEW_GUINEA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PARAGUAY) . '">' . cmtx_define(CMTX_COUNTRY_PARAGUAY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PERU) . '">' . cmtx_define(CMTX_COUNTRY_PERU)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PHILIPPINES) . '">' . cmtx_define(CMTX_COUNTRY_PHILIPPINES)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_POLAND) . '">' . cmtx_define(CMTX_COUNTRY_POLAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PORTUGAL) . '">' . cmtx_define(CMTX_COUNTRY_PORTUGAL)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_PUERTO_RICO) . '">' . cmtx_define(CMTX_COUNTRY_PUERTO_RICO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_QATAR) . '">' . cmtx_define(CMTX_COUNTRY_QATAR)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ROMANIA) . '">' . cmtx_define(CMTX_COUNTRY_ROMANIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_RUSSIA) . '">' . cmtx_define(CMTX_COUNTRY_RUSSIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_RWANDA) . '">' . cmtx_define(CMTX_COUNTRY_RWANDA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SAMOA) . '">' . cmtx_define(CMTX_COUNTRY_SAMOA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SAN_MARINO) . '">' . cmtx_define(CMTX_COUNTRY_SAN_MARINO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SAUDI_ARABIA) . '">' . cmtx_define(CMTX_COUNTRY_SAUDI_ARABIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SENEGAL) . '">' . cmtx_define(CMTX_COUNTRY_SENEGAL)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SERBIA) . '">' . cmtx_define(CMTX_COUNTRY_SERBIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SEYCHELLES) . '">' . cmtx_define(CMTX_COUNTRY_SEYCHELLES)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SIERRA_LEONE) . '">' . cmtx_define(CMTX_COUNTRY_SIERRA_LEONE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SINGAPORE) . '">' . cmtx_define(CMTX_COUNTRY_SINGAPORE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SLOVAKIA) . '">' . cmtx_define(CMTX_COUNTRY_SLOVAKIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SLOVENIA) . '">' . cmtx_define(CMTX_COUNTRY_SLOVENIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SOMALIA) . '">' . cmtx_define(CMTX_COUNTRY_SOMALIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SOUTH_AFRICA) . '">' . cmtx_define(CMTX_COUNTRY_SOUTH_AFRICA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SOUTH_KOREA) . '">' . cmtx_define(CMTX_COUNTRY_SOUTH_KOREA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SPAIN) . '">' . cmtx_define(CMTX_COUNTRY_SPAIN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SRI_LANKA) . '">' . cmtx_define(CMTX_COUNTRY_SRI_LANKA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SUDAN) . '">' . cmtx_define(CMTX_COUNTRY_SUDAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SURINAME) . '">' . cmtx_define(CMTX_COUNTRY_SURINAME)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SWAZILAND) . '">' . cmtx_define(CMTX_COUNTRY_SWAZILAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SWEDEN) . '">' . cmtx_define(CMTX_COUNTRY_SWEDEN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SWITZERLAND) . '">' . cmtx_define(CMTX_COUNTRY_SWITZERLAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_SYRIA) . '">' . cmtx_define(CMTX_COUNTRY_SYRIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TAIWAN) . '">' . cmtx_define(CMTX_COUNTRY_TAIWAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TAJIKISTAN) . '">' . cmtx_define(CMTX_COUNTRY_TAJIKISTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TANZANIA) . '">' . cmtx_define(CMTX_COUNTRY_TANZANIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_THAILAND) . '">' . cmtx_define(CMTX_COUNTRY_THAILAND)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_THE_EMIRATES) . '">' . cmtx_define(CMTX_COUNTRY_THE_EMIRATES)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TOGO) . '">' . cmtx_define(CMTX_COUNTRY_TOGO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TRINIDAD_TOBAGO) . '">' . cmtx_define(CMTX_COUNTRY_TRINIDAD_TOBAGO)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TUNISIA) . '">' . cmtx_define(CMTX_COUNTRY_TUNISIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TURKEY) . '">' . cmtx_define(CMTX_COUNTRY_TURKEY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_TURKMENISTAN) . '">' . cmtx_define(CMTX_COUNTRY_TURKMENISTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_UGANDA) . '">' . cmtx_define(CMTX_COUNTRY_UGANDA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_UKRAINE) . '">' . cmtx_define(CMTX_COUNTRY_UKRAINE)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_URUGUAY) . '">' . cmtx_define(CMTX_COUNTRY_URUGUAY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_UZBEKISTAN) . '">' . cmtx_define(CMTX_COUNTRY_UZBEKISTAN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_VATICAN_CITY) . '">' . cmtx_define(CMTX_COUNTRY_VATICAN_CITY)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_VENEZUELA) . '">' . cmtx_define(CMTX_COUNTRY_VENEZUELA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_VIETNAM) . '">' . cmtx_define(CMTX_COUNTRY_VIETNAM)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_WESTERN_SAHARA) . '">' . cmtx_define(CMTX_COUNTRY_WESTERN_SAHARA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_YEMEN) . '">' . cmtx_define(CMTX_COUNTRY_YEMEN)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ZAMBIA) . '">' . cmtx_define(CMTX_COUNTRY_ZAMBIA)  . '</option>
<option value="' . cmtx_define(CMTX_COUNTRY_ZIMBABWE) . '">' . cmtx_define(CMTX_COUNTRY_ZIMBABWE)  . '</option>
</select>';

?>